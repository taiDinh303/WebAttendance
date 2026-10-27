<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'displayName' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:128', Password::min(12)->mixedCase()->numbers(), 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string'],
            'role' => ['required', Rule::in(['teacher', 'student'])],
            'ownerId' => ['prohibited'], 'accountStatus' => ['prohibited'], 'emailVerifiedAt' => ['prohibited'],
        ];
    }
}
