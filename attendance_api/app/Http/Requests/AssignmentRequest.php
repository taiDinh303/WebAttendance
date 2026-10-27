<?php

namespace App\Http\Requests;

final class AssignmentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:200'], 'instructions' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'dueAt' => ['sometimes', 'nullable', 'date', 'regex:/[Tt].*(?:[Zz]|[+-]\\d{2}:\\d{2})$/'],
            'allowLate' => ['required', 'boolean'], 'allowResubmit' => ['required', 'boolean'],
            'fileIds' => ['sometimes', 'array', 'max:5'], 'fileIds.*' => ['required', 'ulid', 'distinct'],
            'expectedVersion' => [$this->isMethod('PATCH') ? 'required' : 'prohibited', 'integer', 'min:1'],
            'maxScore' => ['prohibited'], 'classId' => ['prohibited'], 'authorId' => ['prohibited'], 'state' => ['prohibited']];
    }
}
