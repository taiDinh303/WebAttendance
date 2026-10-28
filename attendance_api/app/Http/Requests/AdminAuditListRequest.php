<?php

namespace App\Http\Requests;

final class AdminAuditListRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'pageNumber' => ['sometimes', 'integer', 'between:1,1000000'], 'pageSize' => ['sometimes', 'integer', 'between:1,100'],
            'subjectId' => ['sometimes', 'ulid'], 'actorId' => ['sometimes', 'ulid'], 'operation' => ['sometimes', 'in:lock,unlock'],
        ];
    }
}
