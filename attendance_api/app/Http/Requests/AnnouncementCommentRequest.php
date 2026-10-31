<?php

namespace App\Http\Requests;

final class AnnouncementCommentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:20000'],
            'clientMessageId' => ['sometimes', 'nullable', 'ulid', 'max:26'],
        ];
    }
}
