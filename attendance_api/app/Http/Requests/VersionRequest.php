<?php

namespace App\Http\Requests;

final class VersionRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['expectedVersion' => ['required', 'integer', 'min:1'], 'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
            'expiresAt' => ['sometimes', 'nullable', 'date', 'after:now']];
    }
}
