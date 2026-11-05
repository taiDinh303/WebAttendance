<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class GoogleAuthRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['credential' => ['required', 'string', 'max:16384'],
            'role' => ['sometimes', 'nullable', Rule::in(['teacher', 'student'])],
            'email' => ['prohibited'], 'googleSubject' => ['prohibited'], 'emailVerifiedAt' => ['prohibited']];
    }
}
