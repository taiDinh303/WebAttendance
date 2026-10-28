<?php

namespace App\Http\Requests;

final class AdminUserListRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'pageNumber' => ['sometimes', 'integer', 'between:1,1000000'], 'pageSize' => ['sometimes', 'integer', 'between:1,100'],
            'q' => ['sometimes', 'nullable', 'string', 'max:200'],
            'role' => ['sometimes', 'in:admin,teacher,student'], 'status' => ['sometimes', 'in:active,locked'],
            'sort' => ['sometimes', 'in:name,-name,createdAt,-createdAt'],
        ];
    }
}
