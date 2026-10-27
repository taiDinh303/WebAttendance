<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

final class TeacherCatalogPolicy
{
    public function create(User $user): bool
    {
        return $user->role === 'teacher';
    }

    public function update(User $user, Model $catalog): Response
    {
        return $user->role === 'teacher' && $catalog->owner_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
