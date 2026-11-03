<?php

namespace App\Http\Requests;

use App\Actions\Accounts\PortfolioDemo;

final class SubmissionRequest extends ApiRequest
{
    public function rules(): array
    {
        $demo = config('demo.portfolio_enabled') && app(PortfolioDemo::class)->contains($this->user());

        return ['fileIds' => $demo ? ['present', 'array', 'max:0'] : ['required', 'array', 'min:1', 'max:5'], 'fileIds.*' => ['required', 'ulid', 'distinct'],
            'note' => $demo ? ['required', 'string', 'min:3', 'max:2000'] : ['sometimes', 'nullable', 'string', 'max:2000'], 'studentId' => ['prohibited'], 'score' => ['prohibited'],
            'idempotencyKey' => ['required', 'uuid']];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['idempotencyKey' => $this->header('Idempotency-Key')]);
    }
}
