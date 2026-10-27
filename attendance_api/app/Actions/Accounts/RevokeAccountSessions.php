<?php

namespace App\Actions\Accounts;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RevokeAccountSessions
{
    // Called while the user row is locked, in the password-change transaction.
    public function handle(User $user): void
    {
        $user->auth_version++;
        $user->version++;
        $user->remember_token = Str::random(60);
        $user->save();
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
