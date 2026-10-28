<?php

namespace Tests\Feature;

use App\Actions\Accounts\AccountStatus;
use App\Actions\Accounts\ChangeAccountStatus;
use App\Models\AdminAccountEvent;
use App\Models\Classroom;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_requests_without_json_accept_return_401_instead_of_redirecting(): void
    {
        $this->get('/api/v1/me')->assertUnauthorized()->assertJsonPath('code', 'UNAUTHENTICATED');
        $this->get('/api/v1/admin/users')->assertUnauthorized()->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_admin_endpoints_require_a_verified_active_administrator(): void
    {
        $this->getJson('/api/v1/admin/summary')->assertUnauthorized();
        foreach (['teacher', 'student'] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->getJson('/api/v1/admin/summary')->assertForbidden();
            $this->getJson('/api/v1/admin/users')->assertForbidden();
            $this->getJson('/api/v1/admin/audit')->assertForbidden();
            $this->postJson('/api/v1/admin/users/'.$actor->id.'/lock', ['expectedVersion' => 1, 'reason' => 'Test'])->assertForbidden();
            $this->assertFalse(Gate::forUser($actor)->allows('viewAny', User::class));
        }
        $unverified = User::factory()->unverified()->create(['role' => 'admin']);
        $this->actingAs($unverified)->getJson('/api/v1/admin/users')->assertForbidden()->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
        $locked = User::factory()->create(['role' => 'admin', 'account_status' => 'locked']);
        $this->actingAs($locked)->getJson('/api/v1/admin/users')->assertForbidden()->assertJsonPath('code', 'ACCOUNT_LOCKED');
        $this->assertDatabaseCount('admin_account_events', 0);
    }

    public function test_admin_user_list_is_paginated_filtered_and_never_serializes_secrets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create(['role' => 'teacher']);
        $student = User::factory()->create(['display_name' => 'Special % student', 'account_status' => 'locked']);
        $this->actingAs($admin)->getJson('/api/v1/admin/users?role=teacher&pageSize=2')->assertOk()
            ->assertJsonPath('data.totalItems', 3)->assertJsonCount(2, 'data.items')->assertJsonPath('data.hasNextPage', true);
        $response = $this->getJson('/api/v1/admin/users?'.http_build_query(['q' => '%', 'status' => 'locked']))->assertOk()
            ->assertJsonPath('data.totalItems', 1)->assertJsonPath('data.items.0.id', $student->id);
        $this->assertSame(['id', 'displayName', 'email', 'role', 'accountStatus', 'emailVerified', 'version', 'createdAt', 'isSelf'], array_keys($response->json('data.items.0')));
        $this->assertArrayNotHasKey('password', $response->json('data.items.0'));
        $summary = $this->getJson('/api/v1/admin/summary')->assertOk()->assertJsonPath('data.users', 5)
            ->assertJsonPath('data.admins', 1)->assertJsonPath('data.teachers', 3)->assertJsonPath('data.students', 1);
        $this->assertSame(['users', 'admins', 'teachers', 'students', 'lockedAccounts', 'classes', 'activeClasses', 'assignments', 'submissions', 'attendanceRecords', 'openAttendanceWindows', 'adminEvents', 'serverTime'], array_keys($summary->json('data')));
    }

    #[DataProvider('invalidListFilters')]
    public function test_rejects_invalid_filters_without_query_injection(string $query): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->getJson('/api/v1/admin/users?'.$query)->assertUnprocessable();
    }

    public static function invalidListFilters(): array
    {
        return ['page zero' => ['pageNumber=0'], 'oversize page' => ['pageSize=101'], 'unknown role' => ['role=root'], 'status injection' => ['status=active%27%20OR%201%3D1'], 'sort injection' => ['sort=email%3BDROP']];
    }

    public function test_lock_is_atomic_with_audit_and_revokes_database_sessions(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create();
        DB::table('sessions')->insert(['id' => 'synthetic-student-session', 'user_id' => $student->id, 'payload' => 'opaque-test-session', 'last_activity' => time()]);
        $this->actingAs($admin)->postJson('/api/v1/admin/users/'.$student->id.'/lock', ['expectedVersion' => 1, 'reason' => 'Synthetic moderation reason'])
            ->assertOk()->assertJsonPath('data.accountStatus', 'locked')->assertJsonPath('data.version', 2);
        $this->assertDatabaseMissing('sessions', ['id' => 'synthetic-student-session']);
        $this->assertSame(2, $student->fresh()->auth_version);
        $event = AdminAccountEvent::sole();
        $this->assertSame($admin->id, $event->actor_id);
        $this->assertSame($student->id, $event->subject_id);
        $this->assertSame('active', $event->before_state['accountStatus']);
        $this->assertSame(1, $event->before_state['version']);
        $this->assertSame('locked', $event->after_state['accountStatus']);
        $this->assertSame(2, $event->after_state['version']);
        $this->getJson('/api/v1/admin/audit?subjectId='.$student->id.'&operation=lock')->assertOk()
            ->assertJsonPath('data.totalItems', 1)->assertJsonPath('data.items.0.reason', 'Synthetic moderation reason');
        $this->postJson('/api/v1/admin/users/'.$student->id.'/unlock', ['expectedVersion' => 1, 'reason' => 'Stale edit'])->assertConflict()->assertJsonPath('code', 'VERSION_CONFLICT');
        $this->assertDatabaseCount('admin_account_events', 1);
        $this->assertSame('locked', $student->fresh()->account_status);
    }

    public function test_unlock_does_not_reactivate_old_session_versions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create();
        $this->actingAs($admin)->postJson('/api/v1/admin/users/'.$student->id.'/lock', ['expectedVersion' => 1, 'reason' => 'Test lock'])->assertOk();
        $this->actingAs($student)->withSession(['authVersion' => 1])->getJson('/api/v1/me')->assertForbidden();
        $this->actingAs($admin)->withSession(['authVersion' => 1])->postJson('/api/v1/admin/users/'.$student->id.'/unlock', ['expectedVersion' => 2, 'reason' => 'Test unlock'])
            ->assertOk()->assertJsonPath('data.accountStatus', 'active')->assertJsonPath('data.version', 3);
        $this->actingAs($student)->withSession(['authVersion' => 1])->getJson('/api/v1/me')->assertUnauthorized();
        $this->assertSame(3, $student->fresh()->auth_version);
        $this->assertDatabaseCount('admin_account_events', 2);
    }

    public function test_cannot_lock_self_or_remove_the_only_active_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson('/api/v1/admin/users/'.$admin->id.'/lock', ['expectedVersion' => 1, 'reason' => 'Self lock attempt'])
            ->assertConflict()->assertJsonPath('code', 'CANNOT_LOCK_SELF');
        $this->assertSame('active', $admin->fresh()->account_status);
        $this->assertDatabaseCount('admin_account_events', 0);
        $this->assertSame(1, User::where('role', 'admin')->where('account_status', 'active')->count());
    }

    public function test_noop_and_invalid_changes_do_not_create_audit_or_change_versions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['account_status' => 'locked']);
        $this->actingAs($admin)->postJson('/api/v1/admin/users/'.$student->id.'/lock', ['expectedVersion' => 1, 'reason' => 'Already locked'])
            ->assertOk()->assertJsonPath('data.version', 1);
        $this->postJson('/api/v1/admin/users/'.$student->id.'/unlock', ['expectedVersion' => 1, 'reason' => '   '])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->postJson('/api/v1/admin/users/'.$student->id.'/unlock', ['expectedVersion' => 1, 'reason' => 'Test change', 'role' => 'admin', 'password' => 'not-accepted'])
            ->assertUnprocessable()->assertJsonValidationErrors(['role', 'password']);
        $this->assertSame(1, $student->fresh()->auth_version);
        $this->assertDatabaseCount('admin_account_events', 0);
        try {
            app(ChangeAccountStatus::class)->handle($admin, $student->id, AccountStatus::Active, 1, ' ');
        } catch (ValidationException) {
            $this->assertDatabaseCount('admin_account_events', 0);

            return;
        }
        $this->fail('Core moderation action must reject an empty audit reason.');
    }

    public function test_stale_admin_actor_is_reauthorized_under_the_lock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create();
        User::whereKey($admin->id)->update(['account_status' => 'locked']);
        try {
            app(ChangeAccountStatus::class)->handle($admin, $student->id, AccountStatus::Locked, 1, 'Stale actor attempt');
        } catch (AuthorizationException) {
            $this->assertSame('active', $student->fresh()->account_status);
            $this->assertSame('active', $otherAdmin->fresh()->account_status);
            $this->assertDatabaseCount('admin_account_events', 0);

            return;
        }
        $this->fail('A stale admin object must not retain permission after being locked.');
    }

    public function test_admin_has_no_implicit_access_to_classroom_content_or_private_files(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $classId = $this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => 'Private teacher class'])->assertCreated()->json('data.class.id');
        $class = Classroom::findOrFail($classId);
        $file = StoredFile::create(['class_id' => $classId, 'uploaded_by' => $teacher->id, 'purpose' => 'assignment_material', 'storage_key' => 'private-synthetic.txt', 'original_name' => 'private.txt', 'mime_type' => 'text/plain', 'size_bytes' => 1, 'sha256' => str_repeat('0', 64), 'scan_state' => 'validated_local']);
        $this->assertFalse(Gate::forUser($admin)->allows('view', $class));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $class));
        $this->actingAs($admin)->getJson('/api/v1/classes/'.$classId)->assertNotFound();
        $this->getJson('/api/v1/classes/'.$classId.'/assignments')->assertNotFound();
        $this->getJson('/api/v1/classes/'.$classId.'/gradebook')->assertNotFound();
        $this->getJson('/api/v1/files/'.$file->id.'/download')->assertNotFound();
        $this->getJson('/api/v1/dashboard')->assertForbidden();
        $this->getJson('/api/v1/classes')->assertForbidden();
        $this->assertDatabaseCount('admin_account_events', 0);
    }
}
