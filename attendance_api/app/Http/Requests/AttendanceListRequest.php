<?php

namespace App\Http\Requests;

final class AttendanceListRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['pageNumber' => ['sometimes', 'integer', 'between:1,1000000'], 'pageSize' => ['sometimes', 'integer', 'between:1,100'],
            'classId' => ['sometimes', 'ulid'], 'sessionId' => ['sometimes', 'ulid'], 'studentId' => ['sometimes', 'ulid'],
            'status' => ['sometimes', 'string', 'in:present,late,absent'],
            'from' => ['required_with:to', 'date', 'regex:/[Tt].*(?:[Zz]|[+-]\\d{2}:\\d{2})$/'],
            'to' => ['required_with:from', 'date', 'after:from', 'regex:/[Tt].*(?:[Zz]|[+-]\\d{2}:\\d{2})$/']];
    }
}
