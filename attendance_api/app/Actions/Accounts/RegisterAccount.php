<?php

namespace App\Actions\Accounts;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

final class RegisterAccount
{
    public function handle(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = new User(['display_name' => $data['displayName'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->role = $data['role'];
            $user->save();

            return $user;
        });
        event(new Registered($user));

        return $user;
    }
}
