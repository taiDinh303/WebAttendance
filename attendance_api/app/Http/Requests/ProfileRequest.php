<?php

namespace App\Http\Requests;

final class ProfileRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'displayName' => ['sometimes', 'required', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'locale' => ['sometimes', 'in:vi,en'], 'timezone' => ['sometimes', 'timezone:all'],
            'expectedVersion' => ['required', 'integer', 'min:1'],
            'role' => ['prohibited'], 'userId' => ['prohibited'], 'email' => ['prohibited'], 'accountStatus' => ['prohibited'],
        ];
    }
}
