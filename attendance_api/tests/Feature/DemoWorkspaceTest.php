<?php

namespace Tests\Feature;

use App\Actions\Accounts\AccountStatus;
use App\Actions\Accounts\ChangeAccountStatus;
use App\Actions\Accounts\SeedDemoWorkspace;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRoster;
use App\Models\Classroom;
use App\Models\GradePublication;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

final class DemoWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function enableDemo(): void
    {
        config(['demo.enabled' => true, 'demo.password' => 'SyntheticPass123!', 'mail.default' => 'array']);
        Storage::fake('local');
    }

    public function test_demo_has_exact_counts_and_reseed_preserves_moderation_passwords_and_classroom_edits(): void
    {
        $this->enableDemo();
        $demo = app(SeedDemoWorkspace::class);
        $manifest = $demo->handle();
        $this->assertTrue($manifest['created']);
        $this->assertDatabaseCount('users', 94);
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(3, User::where('role', 'teacher')->count());
        $this->assertSame(90, User::where('role', 'student')->count());
        $this->assertDatabaseCount('classes', 3);
        $this->assertDatabaseCount('class_memberships', 90);
        foreach ($manifest['classIds'] as $classId) {
            $this->assertSame(30, DB::table('class_memberships')->where('class_id', $classId)->where('status', 'active')->count());
        }
        $this->assertSame(30, AttendanceRoster::where('window_id', $manifest['liveWindowId'])->count());
        $this->assertSame(180, AttendanceRecord::count());
        $this->assertDatabaseCount('submissions', 45);
        $this->assertSame(30, GradePublication::count());
        $this->assertSame(45, StoredFile::count());
        Storage::disk('local')->assertExists(StoredFile::firstOrFail()->storage_key);
        $admin = User::where('role', 'admin')->sole();
        $student = User::where('email', 'student090@demo.attendance.test')->sole();
        $student->password = 'ChangedDemo456!';
        $student->save();
        app(ChangeAccountStatus::class)->handle($admin, $student->id, AccountStatus::Locked, $student->version, 'Preserve moderation after reseed');
        $class = Classroom::firstOrFail();
        $class->name = 'User edited classroom name';
        $class->save();
        $second = $demo->handle();
        $this->assertFalse($second['created']);
        foreach (['id', 'email', 'role'] as $field) {
            $this->assertSame(array_column($manifest['accounts'], $field), array_column($second['accounts'], $field));
        }
        $this->assertDatabaseCount('users', 94);
        $this->assertDatabaseCount('classes', 3);
        $this->assertDatabaseCount('stored_files', 45);
        $this->assertDatabaseCount('grade_publications', 30);
        $this->assertDatabaseCount('admin_account_events', 1);
        $this->assertSame('locked', $student->fresh()->account_status);
        $this->assertTrue(Hash::check('ChangedDemo456!', $student->fresh()->password));
        $this->assertSame('User edited classroom name', $class->fresh()->name);
    }

    public function test_seed_refuses_without_opt_in_and_does_not_write_data(): void
    {
        config(['demo.enabled' => false]);
        try {
            app(SeedDemoWorkspace::class)->handle();
        } catch (RuntimeException) {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('demo_datasets', 0);

            return;
        }
        $this->fail('Demo seeding requires explicit opt-in.');
    }

    public function test_production_cannot_enable_demo_seed(): void
    {
        $this->enableDemo();
        app()->detectEnvironment(fn () => 'production');
        try {
            app(SeedDemoWorkspace::class)->handle();
        } catch (RuntimeException) {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('demo_datasets', 0);

            return;
        }
        $this->fail('Demo data must never seed into production.');
    }

    public function test_existing_email_namespace_is_not_overwritten_or_partially_seeded(): void
    {
        $this->enableDemo();
        $existing = User::factory()->create(['email' => 'admin@demo.attendance.test', 'role' => 'student']);
        try {
            app(SeedDemoWorkspace::class)->handle();
        } catch (RuntimeException) {
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('demo_datasets', 0);
            $this->assertSame('student', $existing->fresh()->role);
            $this->assertTrue(Hash::check('SyntheticPass123!', $existing->fresh()->password));

            return;
        }
        $this->fail('Existing namespace must not be adopted or overwritten.');
    }

    public function test_seed_refuses_unapproved_database_before_any_queries(): void
    {
        $this->enableDemo();
        $originalDatabase = config('database.connections.mysql.database');
        config(['database.connections.mysql.database' => 'attendance_dev']);
        try {
            app(SeedDemoWorkspace::class)->handle();
        } catch (RuntimeException) {
            config(['database.connections.mysql.database' => $originalDatabase]);
            $this->assertDatabaseCount('users', 0);

            return;
        }
        $this->fail('Demo seed must not write into the ordinary dev database.');
    }
}
