<?php

namespace App\Http\Requests;

final class SemesterRequest extends ApiRequest
{
    public function rules(): array
    {
        // PUT-like payload for PATCH keeps the start/end invariant unambiguous.
        return ['name' => ['required', 'string', 'max:120'], 'startDate' => ['required', 'date_format:Y-m-d'],
            'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'isActive' => ['sometimes', 'boolean'],
            'expectedVersion' => [$this->isMethod('POST') ? 'prohibited' : 'required', 'integer', 'min:1'],
            'ownerId' => ['prohibited']];
    }
}
