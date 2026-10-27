<?php

namespace App\Http\Requests;

final class ForgotPasswordRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:254']];
    }
}
