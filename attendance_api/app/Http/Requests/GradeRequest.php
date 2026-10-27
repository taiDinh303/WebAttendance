<?php

namespace App\Http\Requests;

final class GradeRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['score' => ['required', 'numeric', 'between:0,10', 'decimal:0,2'], 'feedback' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'expectedVersion' => ['required', 'integer', 'min:0'], 'state' => ['prohibited'], 'studentId' => ['prohibited'], 'publishedAt' => ['prohibited']];
    }
}
