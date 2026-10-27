<?php

namespace App\Actions\Assignments;

use App\Exceptions\ApiException;
use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\Classroom;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\PrivateFileValidation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class ManageAssignments
{
    public function save(User $actor, Classroom $classroom, array $data, ?string $assignmentId = null): Assignment
    {
        return DB::transaction(function () use ($actor, $classroom, $data, $assignmentId) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $class);
            if ($class->status !== 'active') {
                throw new ApiException('STATE_CONFLICT', 'The class is archived.');
            }
            $assignment = $assignmentId ? Assignment::where('class_id', $class->id)->whereKey($assignmentId)->lockForUpdate()->firstOrFail()
                : new Assignment(['class_id' => $class->id, 'author_id' => $actor->id]);
            if ($assignmentId && $assignment->version !== (int) $data['expectedVersion']) {
                throw new ApiException('VERSION_CONFLICT', 'Assignment changed.');
            }
            if ($assignment->state === 'closed') {
                throw new ApiException('STATE_CONFLICT', 'Closed assignments cannot be edited.');
            }
            $assignment->title = $data['title'];
            $assignment->allow_late = $data['allowLate'];
            $assignment->allow_resubmit = $data['allowResubmit'];
            foreach (['instructions' => 'instructions', 'dueAt' => 'due_at'] as $key => $column) {
                if (array_key_exists($key, $data)) {
                    $assignment->{$column} = $key === 'dueAt' && $data[$key] ? CarbonImmutable::parse($data[$key])->utc()->startOfSecond() : $data[$key];
                }
            }
            if ($assignmentId) {
                $assignment->version++;
            }
            $assignment->save();
            if (array_key_exists('fileIds', $data)) {
                $ids = $data['fileIds'];
                $files = StoredFile::whereIn('id', $ids)->where('uploaded_by', $actor->id)->where('class_id', $class->id)
                    ->where('assignment_id', $assignment->id)->where('purpose', 'assignment_material')->whereIn('state', ['staged', 'attached'])->lockForUpdate()->get();
                if ($files->count() !== count($ids)) {
                    throw new ApiException('INVALID_FILES', 'Files must belong to this assignment and teacher.', 422);
                }
                foreach ($files as $file) {
                    app(PrivateFileValidation::class)->requireApproved($file->scan_state);
                }
                DB::table('assignment_files')->where('assignment_id', $assignment->id)->delete();
                foreach ($files as $file) {
                    DB::table('assignment_files')->insert(['assignment_id' => $assignment->id, 'file_id' => $file->id]);
                    $file->state = 'attached';
                    $file->save();
                }
            }

            return $assignment;
        });
    }

    public function transition(User $actor, Classroom $classroom, string $id, int $version, string $target): Assignment
    {
        return DB::transaction(function () use ($actor, $classroom, $id, $version, $target) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $class);
            if ($class->status !== 'active') {
                throw new ApiException('STATE_CONFLICT', 'The class is archived.');
            }
            $a = Assignment::where('class_id', $class->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($a->state === $target) {
                return $a;
            }
            if ($a->version !== $version) {
                throw new ApiException('VERSION_CONFLICT', 'Assignment changed.');
            }
            if (($target === 'published' && $a->state !== 'draft') || ($target === 'closed' && $a->state !== 'published')) {
                throw new ApiException('STATE_CONFLICT', 'Invalid assignment transition.');
            }
            $a->state = $target;
            $a->version++;
            $a->save();
            if ($target === 'published') {
                $recipients = ClassMembership::where('class_id', $class->id)->where('status', 'active')->pluck('student_id');
                foreach ($recipients as $recipient) {
                    $key = (string) Str::ulid();
                    $payload = json_encode(['classId' => $class->id, 'assignmentId' => $a->id], JSON_THROW_ON_ERROR);
                    DB::table('inbox_notifications')->insert(['id' => (string) Str::ulid(), 'recipient_id' => $recipient, 'event_key' => $key, 'type' => 'assignment.published', 'resource_id' => $a->id, 'payload' => $payload, 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('outbox_events')->insert(['id' => (string) Str::ulid(), 'event_key' => $key, 'type' => 'assignment.published', 'aggregate_id' => $a->id, 'payload' => $payload, 'created_at' => now(), 'updated_at' => now()]);
                }
            }

            return $a;
        });
    }
}
