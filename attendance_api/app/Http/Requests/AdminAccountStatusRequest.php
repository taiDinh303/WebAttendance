<?php

namespace App\Http\Requests;

final class AdminAccountStatusRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        if (is_string($this->input('reason'))) {
            $this->merge(['reason' => trim($this->input('reason'))]);
        }
    }

    public function rules(): array
    {
        return [
            'expectedVersion' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:3', 'max:500'],
            'role' => ['prohibited'], 'password' => ['prohibited'], 'accountStatus' => ['prohibited'], 'email' => ['prohibited'],
        ];
    }
}
