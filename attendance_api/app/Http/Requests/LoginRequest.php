<?php

namespace App\Http\Requests;

final class LoginRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:254'], 'password' => ['required', 'string', 'max:128'], 'rememberMe' => ['sometimes', 'boolean']];
    }
}
