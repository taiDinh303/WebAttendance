<?php

namespace App\Http\Requests;

final class AttendanceWindowRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['opensAt' => ['required', 'date', 'before_or_equal:now', 'regex:/[Tt].*(?:[Zz]|[+-]\\d{2}:\\d{2})$/'],
            'lateAfter' => ['sometimes', 'nullable', 'date', 'after_or_equal:opensAt', 'before_or_equal:closesAt', 'regex:/[Tt].*(?:[Zz]|[+-]\\d{2}:\\d{2})$/'],
            'closesAt' => ['required', 'date', 'after:now', 'after:opensAt', 'regex:/[Tt].*(?:[Zz]|[+-]\\d{2}:\\d{2})$/']];
    }
}
