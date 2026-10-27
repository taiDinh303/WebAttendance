<?php

namespace App\Http\Requests;

final class PageRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['pageNumber' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'pageSize' => ['sometimes', 'integer', 'between:1,100'],
            'q' => ['sometimes', 'nullable', 'string', 'max:200'], 'sort' => ['sometimes', 'in:name,-name,updatedAt,-updatedAt'],
            'status' => ['sometimes', 'in:active,archived,pending,rejected,left,removed'],
            'semesterId' => ['sometimes', 'ulid'], 'courseId' => ['sometimes', 'ulid'], 'assignmentId' => ['sometimes', 'ulid'], 'read' => ['sometimes', 'boolean']];
    }
}
