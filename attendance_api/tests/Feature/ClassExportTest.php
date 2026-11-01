<?php

namespace Tests\Feature;

use App\Actions\Exports\ExportClassReport;
use App\Actions\Exports\TableWorkbook;
use App\Models\ClassMembership;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

final class ClassExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_totals_exclude_open_windows_and_non_roster_students(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create();
        $class = Classroom::forceCreate(['owner_id' => $teacher->id, 'name' => 'Synthetic totals']);
        ClassMembership::forceCreate(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => now()]);
        foreach (['present', 'late', 'absent', 'open'] as $status) {
            $session = (string) Str::ulid();
            $window = (string) Str::ulid();
            DB::table('class_sessions')->insert(['id' => $session, 'class_id' => $class->id, 'starts_at' => now()->subDay(), 'ends_at' => now()->addHour(), 'status' => 'scheduled']);
            DB::table('attendance_windows')->insert(['id' => $window, 'session_id' => $session, 'opened_by' => $teacher->id, 'opens_at' => now()->subHour(), 'closes_at' => $status === 'open' ? now()->addHour() : now()->subMinute(), 'state' => $status === 'open' ? 'open' : 'closed']);
            DB::table('attendance_rosters')->insert(['id' => (string) Str::ulid(), 'window_id' => $window, 'student_id' => $student->id]);
            if ($status !== 'open') {
                DB::table('attendance_records')->insert(['id' => (string) Str::ulid(), 'session_id' => $session, 'student_id' => $student->id, 'window_id' => $window, 'status' => $status, 'recorded_source' => 'manual']);
            }
        }
        $report = app(ExportClassReport::class)->build($teacher, $class, 'attendance');
        $row = $report[0]['rows'][1];
        $this->assertSame([1, 1, 1, 3, 66.67], array_slice($row, -5));
        $this->assertContains('?', array_slice($row, 6, 4));
        $grades = app(ExportClassReport::class)->build($teacher, $class, 'grades');
        $this->assertSame([0, ''], array_slice($grades[0]['rows'][1], -2));
    }

    public function test_grades_use_current_publication_not_draft_and_keep_ungraded_blank(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create();
        $class = Classroom::forceCreate(['owner_id' => $teacher->id, 'name' => 'Synthetic grades']);
        ClassMembership::forceCreate(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => now()]);
        foreach ([8, 6, null] as $score) {
            $assignment = (string) Str::ulid();
            $submission = (string) Str::ulid();
            $revision = (string) Str::ulid();
            $grade = (string) Str::ulid();
            $publication = (string) Str::ulid();
            DB::table('assignments')->insert(['id' => $assignment, 'class_id' => $class->id, 'author_id' => $teacher->id, 'title' => 'Synthetic task', 'allow_late' => true, 'allow_resubmit' => true, 'state' => 'published']);
            DB::table('submissions')->insert(['id' => $submission, 'assignment_id' => $assignment, 'student_id' => $student->id]);
            DB::table('submission_revisions')->insert(['id' => $revision, 'submission_id' => $submission, 'revision_no' => 1, 'idempotency_key' => (string) Str::uuid(), 'request_hash' => str_repeat('0', 64), 'submitted_at' => now(), 'is_late' => false]);
            DB::table('grades')->insert(['id' => $grade, 'revision_id' => $revision, 'graded_by' => $teacher->id, 'score' => 1, 'state' => 'draft']);
            if ($score !== null) {
                DB::table('grade_publications')->insert(['id' => $publication, 'grade_id' => $grade, 'grade_version' => 1, 'submission_id' => $submission, 'assignment_id' => $assignment, 'revision_id' => $revision, 'student_id' => $student->id, 'published_by' => $teacher->id, 'score' => $score, 'published_at' => now()]);
                DB::table('submissions')->where('id', $submission)->update(['published_grade_publication_id' => $publication]);
            }
        }
        $report = app(ExportClassReport::class)->build($teacher, $class, 'grades');
        $this->assertSame([8.0, 6.0, '', 2, 7.0], array_slice($report[0]['rows'][1], 6));
    }

    public function test_student_code_is_optional_self_managed_and_preserves_leading_zeroes(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->patchJson('/api/v1/me/profile', ['studentCode' => '001234', 'expectedVersion' => 1])->assertOk()->assertJsonPath('data.studentCode', '001234');
        $this->assertSame('001234', $student->fresh()->student_code);
        $this->patchJson('/api/v1/me/profile', ['studentCode' => null, 'expectedVersion' => 2])->assertOk()->assertJsonPath('data.studentCode', null);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher)->patchJson('/api/v1/me/profile', ['studentCode' => '999', 'expectedVersion' => 1])->assertUnprocessable();
    }

    public function test_owner_downloads_valid_workbook_and_other_actors_are_denied(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['display_name' => '=HYPERLINK("bad")', 'student_code' => '001234']);
        $class = Classroom::forceCreate(['owner_id' => $teacher->id, 'name' => 'Synthetic export']);
        ClassMembership::forceCreate(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => now()]);
        foreach (['attendance', 'grades'] as $kind) {
            $sheets = app(ExportClassReport::class)->build($teacher, $class, $kind);
            $this->assertSame($student->display_name, $sheets[0]['rows'][1][2]);
            $this->assertSame('001234', $sheets[0]['rows'][1][1]);
            $path = app(TableWorkbook::class)->write($sheets);
            try {
                $zip = new ZipArchive;
                $this->assertTrue($zip->open($path));
                $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
                $this->assertStringContainsString('inlineStr', $xml);
                $this->assertStringNotContainsString('<f>', $xml);
                $this->assertNotFalse(simplexml_load_string($xml));
                $zip->close();
            } finally {
                unlink($path);
            }
            $url = '/api/v1/classes/'.$class->id.'/'.$kind.'/export';
            $response = $this->actingAs($teacher)->get($url)->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            @unlink($response->baseResponse->getFile()->getPathname());
            $this->actingAs($student)->get($url)->assertNotFound();
            $this->actingAs(User::factory()->create(['role' => 'teacher']))->get($url)->assertNotFound();
            $template = app(ExportClassReport::class)->build($teacher, $class, $kind, true);
            $this->assertCount(1, $template[0]['rows']);
        }
    }
}
