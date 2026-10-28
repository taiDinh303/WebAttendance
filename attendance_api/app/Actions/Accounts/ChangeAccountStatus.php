<?php

namespace App\Actions\Accounts;

use App\Exceptions\ApiException;
use App\Models\AdminAccountEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ChangeAccountStatus
{
    public function __construct(private readonly RevokeAccountSessions $revokeSessions) {}

    public function handle(User $actor, string $subjectId, AccountStatus $status, int $expectedVersion, string $reason): User
    {
        Gate::forUser($actor)->authorize('viewAny', User::class);
        $reason = trim($reason);
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['reason' => ['A reason between 3 and 500 characters is required.']]);
        }

        return DB::transaction(function () use ($actor, $subjectId, $status, $expectedVersion, $reason) {
            // Stable lock order serializes cross-admin moderation and protects the last active administrator.
            $admins = User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $freshActor = $admins->firstWhere('id', $actor->id);
            abort_unless($freshActor, 403);
            $subject = $admins->firstWhere('id', $subjectId) ?? User::whereKey($subjectId)->lockForUpdate()->firstOrFail();
            Gate::forUser($freshActor)->authorize('changeStatus', $subject);
            if ($status === AccountStatus::Locked && $subject->id === $freshActor->id) {
                throw new ApiException('CANNOT_LOCK_SELF', 'You cannot lock your own administrator account.');
            }
            if ($subject->version !== $expectedVersion) {
                throw new ApiException('VERSION_CONFLICT', 'Account changed. Reload before saving.');
            }
            if ($subject->account_status === $status->value) {
                return $subject;
            }
            if ($status === AccountStatus::Locked && $subject->role === 'admin' && $admins->where('account_status', 'active')->count() <= 1) {
                throw new ApiException('LAST_ACTIVE_ADMIN', 'At least one active administrator is required.');
            }
            $before = ['accountStatus' => $subject->account_status, 'version' => $subject->version];
            $subject->account_status = $status->value;
            $this->revokeSessions->handle($subject);
            AdminAccountEvent::create([
                'actor_id' => $freshActor->id, 'subject_id' => $subject->id,
                'operation' => $status === AccountStatus::Locked ? 'lock' : 'unlock', 'reason' => trim($reason),
                'before_state' => $before, 'after_state' => ['accountStatus' => $subject->account_status, 'version' => $subject->version],
            ]);

            return $subject;
        });
    }
}
