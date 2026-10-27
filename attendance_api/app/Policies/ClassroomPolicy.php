<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class ClassroomPolicy
{
    public function create(User $user): bool
    {
        return $user->role === 'teacher';
    }

    public function update(User $user, Classroom $classroom): Response
    {
        return $user->role === 'teacher' && $classroom->owner_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function view(User $user, Classroom $classroom): Response
    {
        if ($user->role === 'teacher' && $classroom->owner_id === $user->id) {
            return Response::allow();
        }
        if ($user->role === 'student' && $classroom->memberships()->where('student_id', $user->id)->where('status', 'active')->exists()) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }
}
