<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class ClassroomRequest extends ApiRequest
{
    public function rules(): array
    {
        $create = $this->isMethod('POST');

        return ['name' => [$create ? 'required' : 'sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'timezone' => ['sometimes', 'timezone:all'],
            'courseId' => ['sometimes', 'nullable', 'ulid', Rule::exists('courses', 'id')->where('owner_id', ($this->user()?->id ?? '__unauthenticated__'))],
            'semesterId' => ['sometimes', 'nullable', 'ulid', Rule::exists('semesters', 'id')->where('owner_id', ($this->user()?->id ?? '__unauthenticated__'))],
            'expectedVersion' => [$create ? 'prohibited' : 'required', 'integer', 'min:1'],
            'ownerId' => ['prohibited'], 'status' => ['prohibited'], 'teacherId' => ['prohibited']];
    }
}
