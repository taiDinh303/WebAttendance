<?php

namespace App\Actions\Assignments;

use App\Exceptions\ApiException;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\GradePublication;
use App\Models\Submission;
use App\Models\SubmissionRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ManageGrades
{
    public function save(User $actor, string $revisionId, array $data): Grade
    {
        return DB::transaction(function () use ($actor, $revisionId, $data) {
            [$class,$assignment,$submission,$revision] = $this->context($actor, $revisionId);
            if ($submission->latest_revision_id !== $revision->id) {
                throw new ApiException('STALE_REVISION', 'Grade the latest submitted revision.');
            }
            $grade = Grade::where('revision_id', $revision->id)->lockForUpdate()->first();
            if (($grade?->version ?? 0) !== (int) $data['expectedVersion']) {
                throw new ApiException('VERSION_CONFLICT', 'Grade changed. Reload before saving.');
            }
            $grade ??= new Grade(['revision_id' => $revision->id]);
            if ($grade->exists) {
                $grade->version++;
            }
            $grade->score = number_format((float) $data['score'], 2, '.', '');
            $grade->feedback = $data['feedback'] ?? null;
            $grade->graded_by = $actor->id;
            $grade->state = 'draft';
            $grade->save();

            return $grade;
        });
    }

    public function publish(User $actor, string $gradeId, array $data): array
    {
        $candidate = Grade::findOrFail($gradeId);

        return DB::transaction(function () use ($actor, $candidate, $data) {
            [$class,$assignment,$submission,$revision] = $this->context($actor, $candidate->revision_id);
            $grade = Grade::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            $existing = GradePublication::where('grade_id', $grade->id)->where('grade_version', (int) $data['expectedVersion'])->first();
            if ($existing) {
                return [$existing, false];
            }
            if ($grade->state === 'published' && $grade->version === (int) $data['expectedVersion']) {
                return [GradePublication::where('grade_id', $grade->id)->orderByDesc('grade_version')->firstOrFail(), false];
            }
            if ($grade->version !== (int) $data['expectedVersion']) {
                throw new ApiException('VERSION_CONFLICT', 'Grade changed.');
            }
            if ($submission->latest_revision_id !== $revision->id) {
                throw new ApiException('STALE_REVISION', 'A newer submission must be graded.');
            }
            $reason = $data['reason'] ?? null;
            if ($submission->published_grade_publication_id && (! is_string($reason) || mb_strlen(trim($reason)) < 3)) {
                throw ValidationException::withMessages(['reason' => ['Explain why the published grade is changing.']]);
            }
            $publication = GradePublication::create(['grade_id' => $grade->id, 'grade_version' => $grade->version, 'submission_id' => $submission->id,
                'assignment_id' => $assignment->id, 'revision_id' => $revision->id, 'student_id' => $submission->student_id, 'published_by' => $actor->id,
                'score' => $grade->score, 'feedback' => $grade->feedback, 'published_at' => now()->startOfSecond()]);
            DB::table('grade_events')->insert(['id' => (string) Str::ulid(), 'submission_id' => $submission->id, 'actor_id' => $actor->id,
                'before_publication_id' => $submission->published_grade_publication_id, 'after_publication_id' => $publication->id,
                'reason' => $reason, 'created_at' => now(), 'updated_at' => now()]);
            $submission->published_grade_publication_id = $publication->id;
            $submission->version++;
            $submission->save();
            $grade->state = 'published';
            $grade->version++;
            $grade->save();
            $key = (string) Str::ulid();
            $payload = json_encode(['classId' => $class->id, 'assignmentId' => $assignment->id, 'publicationId' => $publication->id], JSON_THROW_ON_ERROR);
            DB::table('inbox_notifications')->insert(['id' => (string) Str::ulid(), 'recipient_id' => $submission->student_id, 'event_key' => $key, 'type' => 'grade.published', 'resource_id' => $publication->id, 'payload' => $payload, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('outbox_events')->insert(['id' => (string) Str::ulid(), 'event_key' => $key, 'type' => 'grade.published', 'aggregate_id' => $publication->id, 'payload' => $payload, 'created_at' => now(), 'updated_at' => now()]);

            return [$publication, true];
        });
    }

    private function context(User $actor, string $revisionId): array
    {
        $candidate = SubmissionRevision::with('submission.assignment')->findOrFail($revisionId);
        $class = Classroom::whereKey($candidate->submission->assignment->class_id)->lockForUpdate()->firstOrFail();
        Gate::forUser($actor)->authorize('update', $class);
        if ($class->status !== 'active') {
            throw new ApiException('STATE_CONFLICT', 'The class is archived.');
        }
        $assignment = Assignment::whereKey($candidate->submission->assignment_id)->lockForUpdate()->firstOrFail();
        $submission = Submission::whereKey($candidate->submission_id)->lockForUpdate()->firstOrFail();
        $revision = SubmissionRevision::whereKey($candidate->id)->lockForUpdate()->firstOrFail();

        return [$class, $assignment, $submission, $revision];
    }
}
