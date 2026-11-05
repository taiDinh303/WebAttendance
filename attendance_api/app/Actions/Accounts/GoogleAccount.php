<?php

namespace App\Actions\Accounts;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class GoogleAccount
{
    public function login(array $identity, ?string $role): User
    {
        return DB::transaction(function () use ($identity, $role) {
            $user = User::where('google_subject', $identity['subject'])->lockForUpdate()->first();
            if ($user) {
                $this->active($user);

                return $user;
            }
            if (User::where('email', $identity['email'])->exists()) {
                throw new ApiException('GOOGLE_LINK_REQUIRED', 'Sign in with your password, then link Google in Settings.', 409);
            }
            if (! $role) {
                throw new ApiException('GOOGLE_REGISTRATION_REQUIRED', 'Choose your role on the registration page first.', 409);
            }
            $user = new User(['email' => $identity['email'], 'display_name' => $identity['name'], 'password' => null]);
            $user->role = $role;
            $user->google_subject = $identity['subject'];
            $user->email_verified_at = $identity['authoritative'] ? now() : null;
            $user->save();

            return $user;
        });
    }

    public function link(User $actor, array $identity): User
    {
        return DB::transaction(function () use ($actor, $identity) {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $this->active($user);
            if ($user->email !== $identity['email']) {
                throw new ApiException('GOOGLE_EMAIL_MISMATCH', 'The Google email must match your account email.', 422);
            }
            if ($user->google_subject && $user->google_subject !== $identity['subject']) {
                throw new ApiException('GOOGLE_ALREADY_LINKED', 'Disconnect the current Google account first.', 409);
            }
            if (User::where('google_subject', $identity['subject'])->whereKeyNot($user->id)->exists()) {
                throw new ApiException('GOOGLE_ALREADY_LINKED', 'This Google identity is already linked.', 409);
            }
            $user->google_subject = $identity['subject'];
            if ($identity['authoritative']) {
                $user->email_verified_at ??= now();
            }
            $user->save();

            return $user;
        });
    }

    public function unlink(User $actor): User
    {
        return DB::transaction(function () use ($actor) {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            if (! $user->password || ! $user->hasVerifiedEmail()) {
                throw new ApiException('PASSWORD_REQUIRED', 'Set a password and verify your email before disconnecting Google.', 409);
            }
            $user->google_subject = null;
            $user->save();

            return $user;
        });
    }

    private function active(User $user): void
    {
        if ($user->account_status !== 'active') {
            throw new ApiException('ACCOUNT_LOCKED', 'This account is locked.', 403);
        }
    }
}
