<?php

namespace App\Http\Requests;

final class JoinRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['inviteCode' => ['required', 'string', 'size:16', 'regex:/^[a-fA-F0-9]+$/'],
            'studentId' => ['prohibited'], 'classId' => ['prohibited'], 'status' => ['prohibited']];
    }
}
