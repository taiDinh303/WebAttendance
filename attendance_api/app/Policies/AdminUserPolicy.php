<?php

namespace App\Policies;

use App\Models\User;

final class AdminUserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->role === 'admin' && $actor->account_status === 'active' && $actor->hasVerifiedEmail();
    }

    public function changeStatus(User $actor, User $target): bool
    {
        return $this->viewAny($actor);
    }
}
