<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

final class ChangePasswordRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['currentPassword' => ['required', 'string', 'max:128'],
            'password' => ['required', 'string', 'max:128', Password::min(12)->mixedCase()->numbers(), 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string']];
    }
}
