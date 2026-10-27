<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use App\Models\ClassMembership;
use App\Models\User;
use App\Support\PrivateFileValidation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class AssignmentsGradesTest extends TestCase
{
    use RefreshDatabase;

    private function learningClass(): array
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-27T02:00:00Z'));
        Storage::fake('local');
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create();
        $class = $this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => 'Files', 'timezone' => 'Asia/Ho_Chi_Minh'])->assertCreated()->json('data.class.id');
        ClassMembership::create(['class_id' => $class, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => now(), 'joined_at' => now()]);
        $base = '/api/v1/classes/'.$class;
        $assignment = $this->postJson($base.'/assignments', ['title' => 'Files', 'allowLate' => false, 'allowResubmit' => true, 'dueAt' => now()->addHour()->toISOString()])->assertCreated()->json('data.id');

        return [$teacher, $student, $base, $assignment];
    }

    private function upload(string $base, string $assignment, string $purpose, string $name = 'answer.txt', string $content = 'My answer'): TestResponse
    {
        return $this->post($base.'/files', ['assignmentId' => $assignment, 'purpose' => $purpose, 'file' => UploadedFile::fake()->createWithContent($name, $content)], ['Accept' => 'application/json']);
    }

    public function test_file_material_is_private_until_attached_and_published_and_membership_is_rechecked(): void
    {
        [$teacher, $student, $base, $assignment] = $this->learningClass();
        $file = $this->upload($base, $assignment, 'assignment_material')->assertCreated()->json('data.id');
        $download = '/api/v1/files/'.$file.'/download';
        $this->actingAs($student)->get($download, ['Accept' => 'application/json'])->assertNotFound();
        $this->actingAs($teacher)->patchJson($base.'/assignments/'.$assignment, ['title' => 'Files', 'allowLate' => false, 'allowResubmit' => true, 'fileIds' => [$file], 'expectedVersion' => 1])->assertOk();
        $this->actingAs($student)->get($download, ['Accept' => 'application/json'])->assertNotFound();
        $this->actingAs($teacher)->postJson($base.'/assignments/'.$assignment.'/publish', ['expectedVersion' => 2])->assertOk();
        $this->actingAs($student)->get($download)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($teacher)->deleteJson('/api/v1/files/'.$file)->assertConflict();
        $this->actingAs($student)->postJson($base.'/leave', ['expectedVersion' => 1])->assertOk();
        $this->get($download, ['Accept' => 'application/json'])->assertNotFound();
        $this->actingAs($teacher)->get($download)->assertOk();
    }

    public function test_file_content_validation_scanner_failure_and_staged_deletion(): void
    {
        [, , $base, $assignment] = $this->learningClass();
        foreach ([['fake.pdf', 'Not PDF'], ['program.php', '<?php echo 1;'], ['broken.png', 'not an image'], ['null.txt', "a\0b"]] as [$name, $content]) {
            $this->upload($base, $assignment, 'assignment_material', $name, $content)->assertUnprocessable();
        }
        $this->assertDatabaseCount('stored_files', 0);
        config(['filesafety.scanner' => 'unavailable']);
        $this->upload($base, $assignment, 'assignment_material')->assertStatus(503);
        $this->assertDatabaseCount('stored_files', 0);
        config(['filesafety.scanner' => 'local_validation']);
        $file = $this->upload($base, $assignment, 'assignment_material')->assertCreated()->assertJsonPath('data.scanState', 'validated_local')->json('data.id');
        $this->deleteJson('/api/v1/files/'.$file)->assertNoContent();
        $this->get('/api/v1/files/'.$file.'/download', ['Accept' => 'application/json'])->assertNotFound();
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_deadline_resubmit_policy_and_revision_history_are_enforced(): void
    {
        [$teacher, $student, $base, $assignment] = $this->learningClass();
        $url = $base.'/assignments/'.$assignment;
        $this->postJson($url.'/publish', ['expectedVersion' => 1])->assertOk();
        $file = $this->actingAs($student)->upload($base, $assignment, 'submission')->assertCreated()->json('data.id');
        $key = (string) Str::uuid();
        $first = $this->postJson($url.'/submissions', ['fileIds' => [$file]], ['Idempotency-Key' => $key])->assertCreated()->json('data');
        $second = $this->postJson($url.'/submissions', ['fileIds' => [$file], 'note' => 'Revised'], ['Idempotency-Key' => (string) Str::uuid()])->assertCreated()->assertJsonPath('data.revision.revisionNo', 2)->json('data');
        $this->actingAs($teacher)->putJson('/api/v1/submission-revisions/'.$first['revision']['id'].'/grade', ['score' => 7, 'expectedVersion' => 0])->assertConflict();
        $this->patchJson($url, ['title' => 'Files', 'allowLate' => false, 'allowResubmit' => false, 'expectedVersion' => 2])->assertOk();
        $this->actingAs($student)->postJson($url.'/submissions', ['fileIds' => [$file]], ['Idempotency-Key' => (string) Str::uuid()])->assertConflict();
        $this->travel(2)->hours();
        $this->postJson($url.'/submissions', ['fileIds' => [$file]], ['Idempotency-Key' => $key])->assertOk()->assertJsonPath('data.revision.id', $first['revision']['id']);
        $this->postJson($url.'/submissions', ['fileIds' => [$file]], ['Idempotency-Key' => (string) Str::uuid()])->assertUnprocessable();
        $this->assertDatabaseCount('submission_revisions', 2);
        $this->assertDatabaseHas('submissions', ['id' => $first['submission']['id'], 'latest_revision_id' => $second['revision']['id']]);
    }

    public function test_office_structure_and_size_limits_are_checked_and_production_requires_antivirus(): void
    {
        [, , $base, $assignment] = $this->learningClass();
        foreach ([null, '../escape.txt', 'word/vbaProject.bin'] as $unsafe) {
            $path = tempnam(sys_get_temp_dir(), 'atd-test-');
            try {
                $zip = new \ZipArchive;
                $zip->open($path, \ZipArchive::OVERWRITE);
                $zip->addFromString('[Content_Types].xml', '<Types/>');
                $zip->addFromString('word/document.xml', '<document/>');
                if ($unsafe !== null) {
                    $zip->addFromString($unsafe, 'unsafe');
                }
                $zip->close();
                $response = $this->upload($base, $assignment, 'assignment_material', 'document.docx', file_get_contents($path));
                if ($unsafe === null) {
                    $response->assertCreated()->assertJsonPath('data.mimeType', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                } else {
                    $response->assertUnprocessable();
                }
            } finally {
                unlink($path);
            }
        }
        $this->post($base.'/files', ['assignmentId' => $assignment, 'purpose' => 'assignment_material', 'file' => UploadedFile::fake()->create('large.txt', 20481)], ['Accept' => 'application/json'])->assertUnprocessable();
        app()->instance('env', 'production');
        try {
            try {
                app(PrivateFileValidation::class)->assess(UploadedFile::fake()->createWithContent('production.txt', 'Must require antivirus'));
                $this->fail('Production accepted local validation without antivirus.');
            } catch (ApiException $error) {
                $this->assertSame(503, $error->status);
                $this->assertSame('FILE_SCANNER_UNAVAILABLE', $error->errorCode);
            }
        } finally {
            app()->instance('env', 'testing');
        }
        $this->assertDatabaseCount('stored_files', 1);
    }

    public function test_submission_cannot_use_other_students_files_or_exceed_five_files(): void
    {
        [$teacher, $student, $base, $assignment] = $this->learningClass();
        $url = $base.'/assignments/'.$assignment;
        $this->postJson($url.'/publish', ['expectedVersion' => 1])->assertOk();
        $file = $this->actingAs($student)->upload($base, $assignment, 'submission')->assertCreated()->json('data.id');
        $other = User::factory()->create();
        ClassMembership::create(['class_id' => basename($base), 'student_id' => $other->id, 'status' => 'active', 'requested_at' => now(), 'joined_at' => now()]);
        $this->actingAs($other)->postJson($url.'/submissions', ['fileIds' => [$file]], ['Idempotency-Key' => (string) Str::uuid()])->assertUnprocessable();
        $this->actingAs($student)->postJson($url.'/submissions', ['fileIds' => array_map(fn () => (string) Str::ulid(), range(1, 6))], ['Idempotency-Key' => (string) Str::uuid()])->assertUnprocessable();
        $this->postJson($url.'/submissions', ['fileIds' => [$file], 'studentId' => $other->id], ['Idempotency-Key' => (string) Str::uuid()])->assertUnprocessable();
        $this->postJson($url.'/submissions', ['fileIds' => [$file]])->assertUnprocessable();
        $this->assertDatabaseCount('submissions', 0);
        $this->actingAs($teacher)->postJson($base.'/assignments', ['title' => 'Scale', 'allowLate' => false, 'allowResubmit' => true, 'maxScore' => 100])->assertUnprocessable();
    }

    public function test_average_counts_only_current_published_scores_with_equal_weight_and_includes_zero(): void
    {
        [$teacher, $student, $base, $assignment] = $this->learningClass();
        foreach ([0, 10, 6] as $index => $score) {
            $this->actingAs($teacher);
            if ($index > 0) {
                $assignment = $this->postJson($base.'/assignments', ['title' => 'Task '.$index, 'allowLate' => false, 'allowResubmit' => true])->assertCreated()->json('data.id');
            }
            $url = $base.'/assignments/'.$assignment;
            $this->postJson($url.'/publish', ['expectedVersion' => 1])->assertOk();
            $file = $this->actingAs($student)->upload($base, $assignment, 'submission')->assertCreated()->json('data.id');
            $receipt = $this->postJson($url.'/submissions', ['fileIds' => [$file]], ['Idempotency-Key' => (string) Str::uuid()])->assertCreated()->json('data');
            $gradeUrl = '/api/v1/submission-revisions/'.$receipt['revision']['id'].'/grade';
            $grade = $this->actingAs($teacher)->putJson($gradeUrl, ['score' => $score, 'expectedVersion' => 0])->assertOk()->json('data');
            if ($index < 2) {
                $this->postJson('/api/v1/grades/'.$grade['id'].'/publish', ['expectedVersion' => 1])->assertCreated();
            }
        }
        $this->actingAs($student)->getJson($base.'/grade-average')->assertOk()->assertJsonPath('data.average', 5)->assertJsonPath('data.gradedCount', 2);
        $this->getJson($base.'/grades?pageSize=1')->assertOk()->assertJsonPath('data.totalItems', 2)->assertJsonCount(1, 'data.items');
        $this->getJson('/api/v1/me/submissions?assignmentId='.$assignment)->assertOk()->assertJsonPath('data.totalItems', 1)->assertJsonPath('data.items.0.assignmentId', $assignment)->assertJsonPath('data.items.0.studentName', $student->display_name);
        $this->actingAs($teacher)->getJson($base.'/gradebook')->assertOk()->assertJsonPath('data.items.0.average', 5)->assertJsonPath('data.items.0.gradedCount', 2);
        $this->putJson($gradeUrl, ['score' => 10.01, 'expectedVersion' => 1])->assertUnprocessable();
        $this->actingAs(User::factory()->create(['role' => 'teacher']))->getJson($base.'/gradebook')->assertNotFound();
        $this->actingAs(User::factory()->create())->getJson('/api/v1/me/submissions?assignmentId='.$assignment)->assertOk()->assertJsonPath('data.totalItems', 0);
        $this->getJson('/api/v1/me/submissions?assignmentId=invalid')->assertUnprocessable();
    }

    public function test_submission_and_published_grade_snapshots_are_private_and_idempotent(): void
    {
        Storage::fake('local');
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create();
        $other = User::factory()->create();
        $class = $this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => 'Learning', 'timezone' => 'Asia/Ho_Chi_Minh'])->assertCreated()->json('data.class.id');
        foreach ([$student, $other] as $user) {
            ClassMembership::create(['class_id' => $class, 'student_id' => $user->id, 'status' => 'active', 'requested_at' => now(), 'joined_at' => now()]);
        }
        $base = '/api/v1/classes/'.$class;
        $assignment = $this->postJson($base.'/assignments', ['title' => 'Essay', 'allowLate' => false, 'allowResubmit' => true])->assertCreated()->json('data.id');
        $url = $base.'/assignments/'.$assignment;
        $this->actingAs($student)->getJson($url)->assertNotFound();
        $this->actingAs($teacher)->postJson($url.'/publish', ['expectedVersion' => 1])->assertOk();
        $file = $this->actingAs($student)->post($base.'/files', ['assignmentId' => $assignment, 'purpose' => 'submission', 'file' => UploadedFile::fake()->createWithContent('essay.txt', 'My essay')], ['Accept' => 'application/json'])->assertCreated()->json('data.id');
        $key = (string) Str::uuid();
        $receipt = $this->postJson($url.'/submissions', ['fileIds' => [$file]], ['Idempotency-Key' => $key])->assertCreated()->json('data');
        $this->postJson($url.'/submissions', ['fileIds' => [$file]], ['Idempotency-Key' => $key])->assertOk()->assertJsonPath('data.revision.id', $receipt['revision']['id']);
        $this->postJson($url.'/submissions', ['fileIds' => [$file], 'note' => 'changed'], ['Idempotency-Key' => $key])->assertConflict();
        $this->getJson($base.'/grade-average')->assertOk()->assertJsonPath('data.average', null);
        $revision = '/api/v1/submission-revisions/'.$receipt['revision']['id'].'/grade';
        $grade = $this->actingAs($teacher)->putJson($revision, ['score' => 8, 'expectedVersion' => 0])->assertOk()->json('data');
        $this->actingAs($student)->getJson($revision)->assertNotFound();
        $this->getJson($base.'/grade-average')->assertOk()->assertJsonPath('data.average', null);
        $publish = '/api/v1/grades/'.$grade['id'].'/publish';
        $this->actingAs($teacher)->postJson($publish, ['expectedVersion' => 1])->assertCreated();
        $this->postJson($publish, ['expectedVersion' => 1])->assertOk();
        $this->actingAs($student)->getJson($base.'/grade-average')->assertOk()->assertJsonPath('data.average', 8)->assertJsonPath('data.gradedCount', 1);
        $this->actingAs($other)->getJson('/api/v1/files/'.$file.'/download')->assertNotFound();
        $this->getJson($base.'/grade-average')->assertOk()->assertJsonPath('data.average', null);
        $this->actingAs($teacher)->putJson($revision, ['score' => 9, 'expectedVersion' => 2])->assertOk();
        $this->actingAs($student)->getJson($base.'/grade-average')->assertOk()->assertJsonPath('data.average', 8);
        $this->actingAs($teacher)->postJson($publish, ['expectedVersion' => 3])->assertUnprocessable();
        $this->postJson($publish, ['expectedVersion' => 3, 'reason' => 'Correct marking'])->assertCreated();
        $this->actingAs($student)->getJson($base.'/grade-average')->assertOk()->assertJsonPath('data.average', 9);
        $this->assertDatabaseCount('grade_publications', 2);
        $this->assertDatabaseCount('grade_events', 2);
        $this->assertDatabaseCount('submission_revisions', 1);
    }
}
