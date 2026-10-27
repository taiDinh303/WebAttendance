<?php

namespace App\Http\Requests;

final class SubmissionRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['fileIds' => ['required', 'array', 'min:1', 'max:5'], 'fileIds.*' => ['required', 'ulid', 'distinct'],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'], 'studentId' => ['prohibited'], 'score' => ['prohibited'],
            'idempotencyKey' => ['required', 'uuid']];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['idempotencyKey' => $this->header('Idempotency-Key')]);
    }
}
