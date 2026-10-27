<?php

namespace App\Http\Requests;

final class AttendanceCorrectionRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['status' => ['required', 'string', 'in:present,late,absent'], 'reason' => ['required', 'string', 'min:3', 'max:500'],
            'expectedVersion' => ['required', 'integer', 'min:0'], 'checkedInAt' => ['prohibited']];
    }
}
