<?php

namespace App\Http\Requests;

final class AnnouncementRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'title' => [$this->isMethod('PATCH') ? 'sometimes' : 'required', 'nullable', 'string', 'max:200'],
            'body' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'pinned' => ['sometimes', 'boolean'],
            'hidden' => ['sometimes', 'boolean'],
            'expiresAt' => ['sometimes', 'nullable', 'date', 'regex:/[Tt].*(?:[Zz]|[+-]\d{2}:\d{2})$/'],
            'expectedVersion' => [$this->isMethod('PATCH') ? 'required' : 'prohibited', 'integer', 'min:1'],
            'classId' => ['prohibited'],
            'authorId' => ['prohibited'],
            'state' => ['prohibited'],
        ];
    }
}
