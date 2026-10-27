<?php

namespace App\Http\Requests;

final class SessionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'startsAt' => ['required', 'date', 'regex:/[Tt].*(?:[Zz]|[+-]\\d{2}:\\d{2})$/'],
            'endsAt' => ['required', 'date', 'after:startsAt', 'regex:/[Tt].*(?:[Zz]|[+-]\\d{2}:\\d{2})$/'],
            'title' => ['sometimes', 'nullable', 'string', 'max:200'],
            'room' => ['sometimes', 'nullable', 'string', 'max:120'],
            'allowConflicts' => ['sometimes', 'boolean'],
            'expectedVersion' => [$this->isMethod('PATCH') ? 'required' : 'prohibited', 'integer', 'min:1'],
            'classId' => ['prohibited'], 'status' => ['prohibited'],
        ];
    }
}
