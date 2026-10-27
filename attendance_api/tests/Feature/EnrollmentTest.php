<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function setupClass(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create();
        $data = $this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => 'Synthetic class'])->assertCreated()->json('data');

        return [$teacher, $student, $data['class']['id'], $data['inviteCode']];
    }

    public function test_join_is_pending_deduplicated_and_private_until_owner_approves(): void
    {
        [$teacher, $student, $class, $code] = $this->setupClass();
        $id = $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $code])->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');
        $this->postJson('/api/v1/join-requests', ['inviteCode' => $code])->assertOk()->assertJsonPath('data.id', $id);
        $this->assertDatabaseCount('class_memberships', 1);
        $this->assertDatabaseCount('inbox_notifications', 1);
        $this->assertDatabaseCount('outbox_events', 1);
        $this->getJson('/api/v1/classes/'.$class)->assertNotFound();
        $this->getJson('/api/v1/classes')->assertOk()->assertJsonPath('data.totalItems', 0);
        $this->getJson('/api/v1/me/join-requests')->assertOk()->assertJsonPath('data.items.0.status', 'pending');
        $this->actingAs($teacher)->getJson('/api/v1/classes/'.$class.'/join-requests')->assertOk()->assertJsonPath('data.totalItems', 1);
        $url = '/api/v1/classes/'.$class.'/join-requests/'.$id.'/approve';
        $this->postJson($url, ['expectedVersion' => 1])->assertOk()->assertJsonPath('data.status', 'active');
        $this->postJson($url, ['expectedVersion' => 1])->assertOk();
        $this->assertDatabaseCount('membership_events', 2);
        $this->assertDatabaseCount('inbox_notifications', 2);
        $this->actingAs($student)->getJson('/api/v1/classes/'.$class)->assertOk();
        $this->getJson('/api/v1/classes')->assertOk()->assertJsonPath('data.totalItems', 1);
    }

    public function test_only_owner_can_review_and_membership_must_belong_to_parent_class(): void
    {
        [$teacher, $student, $class, $code] = $this->setupClass();
        $id = $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $code])->json('data.id');
        $other = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($other)->postJson('/api/v1/classes/'.$class.'/join-requests/'.$id.'/approve', ['expectedVersion' => 1])->assertNotFound();
        $otherClass = $this->postJson('/api/v1/classes', ['name' => 'Other'])->json('data.class.id');
        $this->postJson('/api/v1/classes/'.$otherClass.'/join-requests/'.$id.'/approve', ['expectedVersion' => 1])->assertNotFound();
        $this->assertDatabaseHas('class_memberships', ['id' => $id, 'status' => 'pending']);
    }

    public function test_rejected_and_left_students_can_request_again_but_removed_requires_permission(): void
    {
        [$teacher, $student, $class, $code] = $this->setupClass();
        $id = $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $code])->json('data.id');
        $base = '/api/v1/classes/'.$class.'/join-requests/'.$id;
        $this->actingAs($teacher)->postJson($base.'/reject', ['expectedVersion' => 1, 'reason' => 'Try again'])->assertOk();
        $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $code])->assertCreated()->assertJsonPath('data.version', 3);
        $this->actingAs($teacher)->postJson($base.'/approve', ['expectedVersion' => 3])->assertOk();
        $this->postJson('/api/v1/classes/'.$class.'/members/'.$student->id.'/remove', ['expectedVersion' => 4])->assertOk();
        $this->actingAs($student)->getJson('/api/v1/classes/'.$class)->assertNotFound();
        $this->postJson('/api/v1/join-requests', ['inviteCode' => $code])->assertForbidden()->assertJsonPath('code', 'REJOIN_REQUIRES_PERMISSION');
        $this->actingAs($teacher)->postJson($base.'/allow-rejoin', ['expectedVersion' => 5])->assertOk();
        $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $code])->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseCount('class_memberships', 1);
    }

    public function test_leave_revokes_access_immediately_and_preserves_records(): void
    {
        [$teacher, $student, $class, $code] = $this->setupClass();
        $id = $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $code])->json('data.id');
        $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/join-requests/'.$id.'/approve', ['expectedVersion' => 1])->assertOk();
        $this->actingAs($student)->postJson('/api/v1/classes/'.$class.'/leave', ['expectedVersion' => 2])->assertOk();
        $this->getJson('/api/v1/classes/'.$class)->assertNotFound();
        $this->getJson('/api/v1/classes')->assertOk()->assertJsonPath('data.totalItems', 0);
        $this->assertDatabaseHas('class_memberships', ['id' => $id, 'status' => 'left']);
        $this->postJson('/api/v1/join-requests', ['inviteCode' => $code])->assertCreated()->assertJsonPath('data.status', 'pending');
    }

    public function test_rotating_disabling_expiring_and_archiving_make_invite_unusable(): void
    {
        [$teacher, $student, $class, $old] = $this->setupClass();
        $new = $this->postJson('/api/v1/classes/'.$class.'/invite-code/rotate', ['expectedVersion' => 1])->assertOk()->json('data.inviteCode');
        $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $old])->assertUnprocessable();
        DB::table('class_invites')->where('class_id', $class)->update(['expires_at' => now()->subSecond()]);
        $this->postJson('/api/v1/join-requests', ['inviteCode' => $new])->assertUnprocessable();
        $new = $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/invite-code/rotate', ['expectedVersion' => 2])->json('data.inviteCode');
        $this->postJson('/api/v1/classes/'.$class.'/invite-code/disable', ['expectedVersion' => 3])->assertOk();
        $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $new])->assertUnprocessable();
        $new = $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/invite-code/rotate', ['expectedVersion' => 4])->json('data.inviteCode');
        $this->postJson('/api/v1/classes/'.$class.'/archive', ['expectedVersion' => 5])->assertOk();
        $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $new])->assertUnprocessable();
    }

    public function test_notification_inbox_is_recipient_scoped_and_mark_read_is_idempotent(): void
    {
        [$teacher, $student, $class, $code] = $this->setupClass();
        $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $code])->assertCreated();
        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('data.totalItems', 0);
        $notice = $this->actingAs($teacher)->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('data.totalItems', 1);
        $noticeId = $notice->json('data.items.0.id');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T.*Z$/', $notice->json('data.items.0.createdAt'));
        $this->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.unreadCount', 1);
        $this->putJson('/api/v1/notifications/'.$noticeId.'/read')->assertNoContent();
        $this->putJson('/api/v1/notifications/'.$noticeId.'/read')->assertNoContent();
        $this->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.unreadCount', 0);
        $this->actingAs($student)->putJson('/api/v1/notifications/'.$noticeId.'/read')->assertNotFound();
    }

    public function test_membership_mutations_reject_stale_or_invalid_state_and_cancel_is_self_only(): void
    {
        [$teacher, $student, $class, $code] = $this->setupClass();
        $id = $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $code])->json('data.id');
        $otherStudent = User::factory()->create();
        $this->actingAs($otherStudent)->postJson('/api/v1/join-requests/'.$id.'/cancel', ['expectedVersion' => 1])->assertNotFound();
        $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/join-requests/'.$id.'/approve', ['expectedVersion' => 99])->assertConflict();
        $this->actingAs($student)->postJson('/api/v1/join-requests/'.$id.'/cancel', ['expectedVersion' => 1])->assertOk();
        $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/join-requests/'.$id.'/approve', ['expectedVersion' => 2])->assertConflict();
        $this->assertDatabaseHas('class_memberships', ['id' => $id, 'status' => 'left']);
    }
}
