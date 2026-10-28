<?php

namespace Tests\Feature;

use App\Actions\Assignments\ManageGrades;
use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Submission;
use App\Models\SubmissionRevision;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication_and_verified_account(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->getJson('/api/v1/dashboard')->assertForbidden()->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_teacher_counts_only_owned_active_classes_and_latest_unpublished_work(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-28T02:00:00Z'));
        $teacher = User::factory()->create(['role' => 'teacher']);
        $other = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create();
        $class = $this->classroom($teacher);
        $foreign = $this->classroom($other, 'Other owner');
        foreach ([$class, $foreign] as $classroom) {
            $this->membership($classroom, $student, 'pending');
            $assignment = Assignment::create(['allow_late' => false, 'allow_resubmit' => true, 'class_id' => $classroom->id, 'author_id' => $classroom->owner_id, 'title' => $classroom->name.' assignment', 'state' => 'published']);
            $submission = $this->submission($assignment, $student);
            ClassSession::create(['class_id' => $classroom->id, 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)]);
            if ($classroom->id === $class->id) {
                $ownSubmission = $submission;
            }
        }
        $this->actingAs($teacher)->getJson('/api/v1/dashboard')->assertOk()
            ->assertJsonPath('data.summary.activeClasses', 1)->assertJsonPath('data.summary.pendingRequests', 1)
            ->assertJsonPath('data.summary.pendingReviews', 1)->assertJsonPath('data.summary.todaySessions', 1)
            ->assertJsonPath('data.classes.0.id', $class->id)->assertJsonPath('data.reviews.0.submissionId', $ownSubmission->id)
            ->assertJsonCount(1, 'data.sessions')->assertJsonMissing(['classId' => $foreign->id]);
        $grade = app(ManageGrades::class)->save($teacher, $ownSubmission->latest_revision_id, ['score' => 8, 'expectedVersion' => 0]);
        $this->actingAs($teacher)->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.summary.pendingReviews', 1);
        app(ManageGrades::class)->publish($teacher, $grade->id, ['expectedVersion' => $grade->version]);
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.summary.pendingReviews', 0)->assertJsonCount(0, 'data.reviews');
    }

    public function test_student_sees_only_unsubmitted_published_assignments_and_own_published_grades(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $class = $this->classroom($teacher);
        $this->membership($class, $student);
        $pendingClass = $this->classroom($teacher, 'Pending class');
        $this->membership($pendingClass, $student, 'pending');
        $todo = Assignment::create(['allow_late' => false, 'allow_resubmit' => true, 'class_id' => $class->id, 'author_id' => $teacher->id, 'title' => 'Need to submit', 'state' => 'published', 'due_at' => now()->addDay()]);
        Assignment::create(['allow_late' => false, 'allow_resubmit' => true, 'class_id' => $class->id, 'author_id' => $teacher->id, 'title' => 'Hidden draft']);
        Assignment::create(['allow_late' => false, 'allow_resubmit' => true, 'class_id' => $pendingClass->id, 'author_id' => $teacher->id, 'title' => 'Hidden pending class', 'state' => 'published']);
        $done = Assignment::create(['allow_late' => false, 'allow_resubmit' => true, 'class_id' => $class->id, 'author_id' => $teacher->id, 'title' => 'Submitted', 'state' => 'published']);
        $own = $this->submission($done, $student);
        $other = $this->submission($done, $otherStudent);
        $ownGrade = app(ManageGrades::class)->save($teacher, $own->latest_revision_id, ['score' => 8, 'expectedVersion' => 0]);
        $otherGrade = app(ManageGrades::class)->save($teacher, $other->latest_revision_id, ['score' => 4, 'expectedVersion' => 0]);
        app(ManageGrades::class)->publish($teacher, $otherGrade->id, ['expectedVersion' => $otherGrade->version]);
        $response = $this->actingAs($student)->getJson('/api/v1/dashboard')->assertOk()
            ->assertJsonPath('data.summary.pendingAssignments', 1)->assertJsonPath('data.summary.publishedGrades', 0)
            ->assertJsonPath('data.assignments.0.id', $todo->id)->assertJsonCount(0, 'data.grades');
        $this->assertArrayNotHasKey('requests', $response->json('data'));
        $this->assertArrayNotHasKey('reviews', $response->json('data'));
        app(ManageGrades::class)->publish($teacher, $ownGrade->id, ['expectedVersion' => $ownGrade->version]);
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.summary.publishedGrades', 1)
            ->assertJsonPath('data.grades.0.score', 8)->assertJsonCount(1, 'data.grades');
        $membership = ClassMembership::where('class_id', $class->id)->where('student_id', $student->id)->firstOrFail();
        $this->getJson('/api/v1/classes/'.$class->id)->assertOk()->assertJsonPath('data.membershipVersion', $membership->version);
        $this->postJson('/api/v1/classes/'.$class->id.'/leave', ['expectedVersion' => $membership->version])->assertOk();
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.summary.activeClasses', 0)
            ->assertJsonPath('data.summary.pendingAssignments', 0)->assertJsonPath('data.summary.publishedGrades', 0)->assertJsonCount(0, 'data.grades');
    }

    public function test_summary_counts_are_not_limited_by_dashboard_preview_sizes(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        for ($index = 0; $index < 8; $index++) {
            $this->classroom($teacher, 'Class '.$index);
        }
        $class = Classroom::firstOrFail();
        for ($index = 0; $index < 7; $index++) {
            $this->membership($class, User::factory()->create(), 'pending');
        }
        $this->actingAs($teacher)->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.summary.activeClasses', 8)
            ->assertJsonCount(6, 'data.classes')->assertJsonPath('data.summary.pendingRequests', 7)
            ->assertJsonCount(1, 'data.requests')->assertJsonPath('data.requests.0.pendingCount', 7);
        $class->forceFill(['status' => 'archived'])->save();
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.summary.activeClasses', 7)->assertJsonPath('data.summary.pendingRequests', 0);
    }

    public function test_today_uses_account_timezone_and_upcoming_excludes_cancelled_and_finished_sessions(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-28T17:30:00Z'));
        $teacher = User::factory()->create(['role' => 'teacher', 'timezone' => 'Asia/Ho_Chi_Minh']);
        $class = $this->classroom($teacher);
        foreach ([['2026-10-28T16:00:00Z', 'scheduled'], ['2026-10-28T18:00:00Z', 'scheduled'], ['2026-10-28T20:00:00Z', 'cancelled']] as [$start, $status]) {
            ClassSession::create(['class_id' => $class->id, 'starts_at' => $start, 'ends_at' => CarbonImmutable::parse($start)->addHour(), 'status' => $status]);
        }
        $this->actingAs($teacher)->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.summary.todaySessions', 1)
            ->assertJsonPath('data.summary.upcomingSessions', 1)->assertJsonCount(1, 'data.sessions');
    }

    private function classroom(User $teacher, string $name = 'Dashboard class'): Classroom
    {
        return Classroom::findOrFail($this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => $name])->assertCreated()->json('data.class.id'));
    }

    private function membership(Classroom $classroom, User $student, string $status = 'active'): void
    {
        ClassMembership::create(['class_id' => $classroom->id, 'student_id' => $student->id, 'status' => $status, 'requested_at' => now()]);
    }

    private function submission(Assignment $assignment, User $student): Submission
    {
        $submission = Submission::create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);
        $revision = SubmissionRevision::create(['submission_id' => $submission->id, 'revision_no' => 1, 'is_late' => false, 'idempotency_key' => (string) Str::uuid(), 'request_hash' => str_repeat('0', 64), 'submitted_at' => now()]);
        $submission->latest_revision_id = $revision->id;
        $submission->save();

        return $submission;
    }
}
