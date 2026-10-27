<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class CourseRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('courses', 'code')->where('owner_id', ($this->user()?->id ?? '__unauthenticated__'))->ignore($this->route('course'))],
            'expectedVersion' => [$this->isMethod('POST') ? 'prohibited' : 'required', 'integer', 'min:1'],
            'ownerId' => ['prohibited']];
    }
}
