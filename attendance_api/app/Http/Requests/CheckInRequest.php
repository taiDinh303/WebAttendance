<?php

namespace App\Http\Requests;

final class CheckInRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['qrToken' => ['required', 'string', 'max:2048'], 'studentId' => ['prohibited'], 'status' => ['prohibited'], 'checkedInAt' => ['prohibited']];
    }
}
