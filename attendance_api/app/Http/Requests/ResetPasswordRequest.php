<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:254'], 'token' => ['required', 'string', 'max:256'],
            'password' => ['required', 'string', 'max:128', Password::min(12)->mixedCase()->numbers(), 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string']];
    }
}
