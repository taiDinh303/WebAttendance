<?php

namespace App\Http\Requests;

final class AnnouncementPageRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'pageNumber' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'between:1,100'],
            'q' => ['sometimes', 'nullable', 'string', 'max:200'],
            'state' => ['sometimes', 'in:draft,published,archived'],
            'pinned' => ['sometimes', 'boolean'],
        ];
    }
}
