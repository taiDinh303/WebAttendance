<?php

namespace App\Http\Requests;

final class CalendarRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['from' => ['required', 'date', 'regex:/[Tt].*(?:[Zz]|[+-]\\d{2}:\\d{2})$/'],
            'to' => ['required', 'date', 'after:from', 'regex:/[Tt].*(?:[Zz]|[+-]\\d{2}:\\d{2})$/'],
            'classId' => ['sometimes', 'ulid'], 'pageNumber' => ['sometimes', 'integer', 'between:1,1000000'],
            'pageSize' => ['sometimes', 'integer', 'between:1,100'], 'status' => ['sometimes', 'string', 'in:scheduled,cancelled']];
    }
}
