<?php

namespace App\Actions\Enrollment;

use App\Exceptions\ApiException;
use App\Models\ClassInvite;
use App\Models\ClassMembership;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class ManageEnrollment
{
    public function requestJoin(User $actor, string $code): array
    {
        abort_unless($actor->role === 'student', 403);

        return DB::transaction(function () use ($actor, $code) {
            $hash = hash('sha256', strtoupper(trim($code)));
            $candidate = ClassInvite::where('code_hash', $hash)->first();
            if (! $candidate) {
                throw new ApiException('INVALID_INVITE_CODE', 'This invite code is invalid or unavailable.', 422);
            }
            // Same parent-first lock order as rotate/archive/review operations.
            $class = Classroom::whereKey($candidate->class_id)->lockForUpdate()->firstOrFail();
            $invite = ClassInvite::where('class_id', $class->id)->where('code_hash', $hash)->first();
            if (! $invite || ! $invite->enabled || ($invite->expires_at && $invite->expires_at->isPast()) || $class->status !== 'active') {
                throw new ApiException('INVALID_INVITE_CODE', 'This invite code is invalid or unavailable.', 422);
            }
            $membership = ClassMembership::where('class_id', $class->id)->where('student_id', $actor->id)->lockForUpdate()->first();
            if ($membership && in_array($membership->status, ['pending', 'active'], true)) {
                return [$membership, false];
            }
            if ($membership?->status === 'removed') {
                throw new ApiException('REJOIN_REQUIRES_PERMISSION', 'Ask the teacher to allow another join request.', 403);
            }
            $previous = $membership?->status;
            $membership ??= new ClassMembership(['class_id' => $class->id, 'student_id' => $actor->id]);
            if ($membership->exists) {
                $membership->version++;
            }
            $membership->status = 'pending';
            $membership->requested_at = now();
            $membership->reviewed_at = null;
            $membership->reviewed_by = null;
            $membership->review_note = null;
            $membership->ended_at = null;
            $membership->save();
            $this->record($membership, $actor, $previous, 'enrollment.requested', $class->owner_id);

            return [$membership->load('classroom'), true];
        });
    }

    public function ownerTransition(User $actor, Classroom $classroom, string $membershipId, string $operation, array $data): ClassMembership
    {
        return DB::transaction(function () use ($actor, $classroom, $membershipId, $operation, $data) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $class);
            if ($class->status !== 'active') {
                throw new ApiException('STATE_CONFLICT', 'The class is archived.');
            }
            $member = ClassMembership::where('class_id', $class->id)->whereKey($membershipId)->lockForUpdate()->firstOrFail();
            $target = match ($operation) {
                'approve' => 'active', 'reject' => 'rejected', 'remove' => 'removed', 'allow-rejoin' => 'left'
            };
            if ($member->status === $target) {
                return $member->load('student');
            }
            $this->requireVersion($member, $data['expectedVersion']);
            $expected = match ($operation) {
                'approve', 'reject' => 'pending', 'remove' => 'active', 'allow-rejoin' => 'removed'
            };
            if ($member->status !== $expected) {
                throw new ApiException('STATE_CONFLICT', 'This membership cannot make that transition.');
            }
            $previous = $member->status;
            $member->status = $target;
            $member->version++;
            $member->reviewed_by = $actor->id;
            $member->reviewed_at = now();
            $member->review_note = $data['reason'] ?? null;
            if ($target === 'active') {
                $member->joined_at = now();
                $member->ended_at = null;
            } elseif (in_array($target, ['removed', 'left'], true)) {
                $member->ended_at = now();
            }
            $member->save();
            $eventType = match ($operation) {
                'approve' => 'enrollment.approved', 'reject' => 'enrollment.rejected', 'remove' => 'enrollment.removed', 'allow-rejoin' => 'enrollment.rejoin-allowed'
            };
            $this->record($member, $actor, $previous, $eventType, $member->student_id);

            return $member->load('student');
        });
    }

    public function selfTransition(User $actor, string $membershipId, string $operation, int $version): ClassMembership
    {
        abort_unless($actor->role === 'student', 403);
        $candidate = ClassMembership::whereKey($membershipId)->where('student_id', $actor->id)->firstOrFail();

        return DB::transaction(function () use ($actor, $candidate, $operation, $version) {
            $class = Classroom::whereKey($candidate->class_id)->lockForUpdate()->firstOrFail();
            $member = ClassMembership::whereKey($candidate->id)->where('student_id', $actor->id)->lockForUpdate()->firstOrFail();
            if ($member->status === 'left') {
                return $member;
            }
            $this->requireVersion($member, $version);
            if ($member->status !== ($operation === 'cancel' ? 'pending' : 'active')) {
                throw new ApiException('STATE_CONFLICT', 'This membership cannot make that transition.');
            }
            $previous = $member->status;
            $member->status = 'left';
            $member->ended_at = now();
            $member->version++;
            $member->save();
            $this->record($member, $actor, $previous, 'enrollment.left', $class->owner_id);

            return $member;
        });
    }

    private function requireVersion(ClassMembership $member, int $version): void
    {
        if ($member->version !== $version) {
            throw new ApiException('VERSION_CONFLICT', 'Membership changed. Reload before saving.');
        }
    }

    private function record(ClassMembership $member, User $actor, ?string $previous, string $type, string $recipient): void
    {
        $eventId = (string) Str::ulid();
        $now = now();
        DB::table('membership_events')->insert(['id' => (string) Str::ulid(), 'membership_id' => $member->id, 'actor_id' => $actor->id, 'from_status' => $previous, 'to_status' => $member->status, 'reason' => $member->review_note, 'created_at' => $now, 'updated_at' => $now]);
        $payload = json_encode(['classId' => $member->class_id, 'membershipId' => $member->id, 'status' => $member->status], JSON_THROW_ON_ERROR);
        DB::table('inbox_notifications')->insert(['id' => (string) Str::ulid(), 'recipient_id' => $recipient, 'event_key' => $eventId, 'type' => $type, 'resource_id' => $member->id, 'payload' => $payload, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('outbox_events')->insert(['id' => (string) Str::ulid(), 'event_key' => $eventId, 'type' => $type, 'aggregate_id' => $member->id, 'payload' => $payload, 'created_at' => $now, 'updated_at' => $now]);
    }
}
