<?php

namespace App\Actions\Announcements;

use App\Exceptions\ApiException;
use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\ClassMembership;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class ManageAnnouncements
{
    /**
     * Create or update an announcement (draft or published edits).
     *
     * @param  array{title?: string, body?: string, pinned?: bool, hidden?: bool, expiresAt?: string|null, expectedVersion?: int}  $data
     */
    public function save(User $actor, Classroom $classroom, array $data, ?string $announcementId = null): Announcement
    {
        return DB::transaction(function () use ($actor, $classroom, $data, $announcementId) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $class);
            if ($class->status !== 'active') {
                throw new ApiException('STATE_CONFLICT', 'The class is archived.');
            }
            if ($announcementId) {
                $announcement = Announcement::where('class_id', $class->id)->whereKey($announcementId)->lockForUpdate()->firstOrFail();
            } else {
                $announcement = new Announcement;
                $announcement->class_id = $class->id;
                $announcement->author_id = $actor->id;
            }
            if ($announcementId && $announcement->version !== (int) $data['expectedVersion']) {
                throw new ApiException('VERSION_CONFLICT', 'Announcement changed.');
            }
            $announcement->title = $data['title'] ?? $announcement->title;
            if (array_key_exists('body', $data)) {
                $announcement->body = $data['body'];
            }
            $announcement->pinned = $data['pinned'] ?? $announcement->pinned;
            $announcement->hidden = $data['hidden'] ?? $announcement->hidden;
            if (array_key_exists('expiresAt', $data)) {
                $announcement->expires_at = $data['expiresAt'] ? $data['expiresAt'] : null;
            }
            if ($announcementId) {
                $announcement->version++;
            }
            $announcement->save();

            return $announcement;
        });
    }

    public function publish(User $actor, Classroom $classroom, string $id, int $version): Announcement
    {
        return DB::transaction(function () use ($actor, $classroom, $id, $version) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $class);
            if ($class->status !== 'active') {
                throw new ApiException('STATE_CONFLICT', 'The class is archived.');
            }
            $announcement = Announcement::where('class_id', $class->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($announcement->version !== $version) {
                throw new ApiException('VERSION_CONFLICT', 'Announcement changed.');
            }
            if ($announcement->state !== 'draft') {
                throw new ApiException('STATE_CONFLICT', 'Only draft announcements can be published.');
            }
            $announcement->state = 'published';
            $announcement->published_at = now();
            $announcement->version++;
            $announcement->save();

            foreach ($this->activeRecipients($class->id) as $recipientId) {
                $key = (string) Str::ulid();
                $payload = json_encode(['classId' => $class->id, 'announcementId' => $announcement->id], JSON_THROW_ON_ERROR);
                DB::table('inbox_notifications')->insert([
                    'id' => (string) Str::ulid(), 'recipient_id' => $recipientId, 'event_key' => $key,
                    'type' => 'announcement.published', 'resource_id' => $announcement->id,
                    'payload' => $payload, 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('outbox_events')->insert([
                    'id' => (string) Str::ulid(), 'event_key' => $key, 'type' => 'announcement.published',
                    'aggregate_id' => $announcement->id, 'payload' => $payload,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return $announcement;
        });
    }

    public function addComment(User $actor, Classroom $classroom, string $announcementId, string $body, ?string $clientMessageId = null): AnnouncementComment
    {
        return DB::transaction(function () use ($actor, $classroom, $announcementId, $body, $clientMessageId) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('view', $class);
            $announcement = Announcement::where('class_id', $class->id)->whereKey($announcementId)->lockForUpdate()->firstOrFail();
            if ($class->status !== 'active' || ($actor->role === 'student' && $announcement->hidden)) {
                abort(404);
            }
            if ($announcement->state !== 'published') {
                throw new ApiException('STATE_CONFLICT', 'Comments are only allowed on published announcements.');
            }
            if ($actor->role === 'student') {
                $active = ClassMembership::where('class_id', $class->id)->where('student_id', $actor->id)->where('status', 'active')->exists();
                abort_unless($active, 403);
            }
            // Idempotent: same client id returns existing comment
            if ($clientMessageId !== null) {
                $existing = AnnouncementComment::withTrashed()->where('announcement_id', $announcement->id)
                    ->where('author_id', $actor->id)
                    ->where('id', $clientMessageId)
                    ->first();
                if ($existing) {
                    if ($existing->body !== $body) {
                        throw new ApiException('VERSION_CONFLICT', 'Comment retry payload changed.');
                    }

                    return $existing;
                }
            }
            if ($clientMessageId !== null && AnnouncementComment::withTrashed()->whereKey($clientMessageId)->exists()) {
                throw new ApiException('VERSION_CONFLICT', 'Comment request key is unavailable.');
            }
            $comment = new AnnouncementComment;
            $comment->id = $clientMessageId ?? (string) Str::ulid();
            $comment->announcement_id = $announcement->id;
            $comment->author_id = $actor->id;
            $comment->body = mb_substr($body, 0, 20000);
            $comment->save();

            return $comment;
        });
    }

    /**
     * Teacher moderation: hide/unhide a comment.
     */
    public function moderateComment(User $actor, Classroom $classroom, string $commentId, bool $hidden): AnnouncementComment
    {
        return DB::transaction(function () use ($actor, $classroom, $commentId, $hidden) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $class);
            $comment = AnnouncementComment::whereKey($commentId)->lockForUpdate()->firstOrFail();
            if ($comment->announcement->class_id !== $class->id) {
                throw new ApiException('NOT_FOUND', 'Comment does not belong to this class.', 404);
            }
            $before = ['hidden' => $comment->hidden];
            $comment->hidden = $hidden;
            $comment->version++;
            $comment->save();
            $this->recordAudit($comment->id, $actor->id, $hidden ? 'hidden' : 'unhidden', $before, ['hidden' => $hidden]);

            return $comment;
        });
    }

    /**
     * Teacher moderation: soft-delete a comment so the author learns it was removed.
     */
    public function deleteComment(User $actor, Classroom $classroom, string $commentId): void
    {
        DB::transaction(function () use ($actor, $classroom, $commentId) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $class);
            $comment = AnnouncementComment::whereKey($commentId)->lockForUpdate()->firstOrFail();
            if ($comment->announcement->class_id !== $class->id) {
                throw new ApiException('NOT_FOUND', 'Comment does not belong to this class.', 404);
            }
            $before = ['hidden' => $comment->hidden, 'body' => $comment->body];
            $comment->version++;
            $comment->delete();
            $this->recordAudit($comment->id, $actor->id, 'deleted', $before, null);

            $recipient = $comment->author_id;
            $key = (string) Str::ulid();
            $payload = json_encode(['classId' => $class->id, 'announcementId' => $comment->announcement_id, 'commentId' => $comment->id], JSON_THROW_ON_ERROR);
            DB::table('inbox_notifications')->insert([
                'id' => (string) Str::ulid(), 'recipient_id' => $recipient, 'event_key' => $key,
                'type' => 'announcement.comment.removed', 'resource_id' => $comment->id,
                'payload' => $payload, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('outbox_events')->insert([
                'id' => (string) Str::ulid(), 'event_key' => $key, 'type' => 'announcement.comment.removed',
                'aggregate_id' => $comment->id, 'payload' => $payload,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    private function recordAudit(string $commentId, string $actorId, string $action, ?array $before, ?array $after): void
    {
        DB::table('announcement_comment_audits')->insert([
            'id' => (string) Str::ulid(), 'comment_id' => $commentId, 'actor_id' => $actorId,
            'action' => $action,
            'before_data' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after_data' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Owner archives an announcement (keeps history, no notification deletion).
     */
    public function archive(User $actor, Classroom $classroom, string $id, int $version): Announcement
    {
        return DB::transaction(function () use ($actor, $classroom, $id, $version) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $class);
            if ($class->status !== 'active') {
                throw new ApiException('STATE_CONFLICT', 'The class is archived.');
            }
            $announcement = Announcement::where('class_id', $class->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($announcement->version !== $version) {
                throw new ApiException('VERSION_CONFLICT', 'Announcement changed.');
            }
            if ($announcement->state === 'archived') {
                return $announcement;
            }
            $announcement->state = 'archived';
            $announcement->version++;
            $announcement->save();

            return $announcement;
        });
    }

    private function activeRecipients(string $classId): array
    {
        return ClassMembership::where('class_id', $classId)->where('status', 'active')->pluck('student_id')->all();
    }
}
