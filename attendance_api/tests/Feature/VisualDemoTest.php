<?php

namespace Tests\Feature;

use App\Actions\Accounts\SeedVisualDemo;
use App\Models\ClassMembership;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

final class VisualDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cloud_shape_seed_has_exact_counts_and_rerun_preserves_existing_accounts(): void
    {
        config(['demo.visual_enabled' => true, 'demo.visual_password' => 'SyntheticPass123!', 'mail.default' => 'array']);
        $teachers = [];
        foreach (range(1, 3) as $number) {
            $teacher = User::factory()->create(['email' => sprintf('gv%03d@attendance.test', $number), 'role' => 'teacher', 'password' => 'OriginalPass123!']);
            $teachers[] = $teacher;
            $class = Classroom::forceCreate(['owner_id' => $teacher->id, 'name' => 'Existing synthetic '.$number]);
            foreach (range(1, 10) as $i) {
                $student = User::factory()->create(['email' => sprintf('hs%03d@attendance.test', ($number - 1) * 10 + $i)]);
                ClassMembership::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => now(), 'joined_at' => now()]);
            }
        }
        $seed = app(SeedVisualDemo::class);
        $first = $seed->handle();
        $this->assertTrue($first['created']);
        $this->assertDatabaseCount('users', 183);
        $this->assertDatabaseCount('classes', 9);
        foreach ($teachers as $teacher) {
            $sizes = Classroom::where('owner_id', $teacher->id)->get()->map(fn ($c) => ClassMembership::where('class_id', $c->id)->where('status', 'active')->count())->sort()->values()->all();
            $this->assertSame([10, 20, 30], $sizes);
            $this->assertTrue(Hash::check('OriginalPass123!', $teacher->fresh()->password));
        }
        $this->assertSame(180, ClassMembership::where('status', 'active')->count());
        $this->assertSame(6, ClassMembership::where('status', 'pending')->count());
        foreach (['class_sessions' => 63, 'attendance_records' => 720, 'attendance_audits' => 720, 'assignments' => 36, 'submissions' => 432, 'grade_publications' => 216, 'announcements' => 27, 'announcement_comments' => 72] as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $class = Classroom::findOrFail($first['classIds'][0]);
        $class->name = 'Preserve edits';
        $class->save();
        $second = $seed->handle();
        $this->assertFalse($second['created']);
        $this->assertSame($first['classIds'], $second['classIds']);
        $this->assertSame('Preserve edits', $class->fresh()->name);
        $this->assertDatabaseCount('attendance_records', 720);
    }

    public function test_seed_requires_opt_in_before_any_write(): void
    {
        config(['demo.visual_enabled' => false]);
        try {
            app(SeedVisualDemo::class)->handle();
            $this->fail('Guard must reject');
        } catch (RuntimeException) {
            $this->assertSame(0, DB::table('demo_datasets')->count());
        }
    }
}
