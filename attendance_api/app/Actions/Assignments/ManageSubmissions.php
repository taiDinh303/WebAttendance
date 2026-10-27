<?php

namespace App\Actions\Assignments;

use App\Exceptions\ApiException;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\StoredFile;
use App\Models\Submission;
use App\Models\SubmissionRevision;
use App\Models\User;
use App\Support\PrivateFileValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class ManageSubmissions
{
    public function submit(User $actor, Classroom $classroom, string $id, array $data): array
    {
        abort_unless($actor->role === 'student', 403);

        return DB::transaction(function () use ($actor, $classroom, $id, $data) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('view', $class);
            $a = Assignment::where('class_id', $class->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            $submission = Submission::where('assignment_id', $a->id)->where('student_id', $actor->id)->lockForUpdate()->first();
            $ids = $data['fileIds'];
            sort($ids);
            $fingerprint = hash('sha256', json_encode(['fileIds' => $ids, 'note' => $data['note'] ?? null], JSON_THROW_ON_ERROR));
            if ($submission) {
                $existing = SubmissionRevision::where('submission_id', $submission->id)->where('idempotency_key', $data['idempotencyKey'])->first();
                if ($existing) {
                    if (! hash_equals($existing->request_hash, $fingerprint)) {
                        throw new ApiException('IDEMPOTENCY_CONFLICT', 'This key was used with different submission data.');
                    }

                    return [$submission, $existing, false];
                }
            }
            if ($class->status !== 'active' || $a->state !== 'published') {
                throw new ApiException('STATE_CONFLICT', 'The assignment is not accepting submissions.');
            }
            $late = $a->due_at !== null && now()->gt($a->due_at);
            if ($late && ! $a->allow_late) {
                throw new ApiException('DEADLINE_PASSED', 'The submission deadline has passed.', 422);
            }
            if ($submission && ! $a->allow_resubmit) {
                throw new ApiException('RESUBMISSION_DISABLED', 'Resubmission is disabled.');
            }
            $files = StoredFile::whereIn('id', $ids)->where('class_id', $class->id)->where('assignment_id', $a->id)->where('uploaded_by', $actor->id)
                ->where('purpose', 'submission')->whereIn('state', ['staged', 'attached'])->lockForUpdate()->get();
            if ($files->count() !== count($ids)) {
                throw new ApiException('INVALID_FILES', 'Files must be your own uploads for this assignment.', 422);
            }
            foreach ($files as $file) {
                app(PrivateFileValidation::class)->requireApproved($file->scan_state);
            }
            if (! $submission) {
                $submission = Submission::create(['assignment_id' => $a->id, 'student_id' => $actor->id]);
            }
            $number = (int) SubmissionRevision::where('submission_id', $submission->id)->max('revision_no') + 1;
            $revision = SubmissionRevision::create(['submission_id' => $submission->id, 'revision_no' => $number, 'idempotency_key' => $data['idempotencyKey'],
                'request_hash' => $fingerprint, 'note' => $data['note'] ?? null, 'submitted_at' => now()->startOfSecond(), 'is_late' => $late]);
            foreach ($files as $file) {
                DB::table('submission_files')->insert(['revision_id' => $revision->id, 'file_id' => $file->id]);
                $file->state = 'attached';
                $file->save();
            }
            $submission->latest_revision_id = $revision->id;
            if ($number > 1) {
                $submission->version++;
            }
            $submission->save();
            $key = (string) Str::ulid();
            $payload = json_encode(['classId' => $class->id, 'assignmentId' => $a->id, 'submissionId' => $submission->id, 'revisionId' => $revision->id], JSON_THROW_ON_ERROR);
            DB::table('inbox_notifications')->insert(['id' => (string) Str::ulid(), 'recipient_id' => $class->owner_id, 'event_key' => $key, 'type' => 'submission.created', 'resource_id' => $submission->id, 'payload' => $payload, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('outbox_events')->insert(['id' => (string) Str::ulid(), 'event_key' => $key, 'type' => 'submission.created', 'aggregate_id' => $submission->id, 'payload' => $payload, 'created_at' => now(), 'updated_at' => now()]);

            return [$submission, $revision, true];
        });
    }
}
