<?php

namespace App\Http\Requests;

final class SeriesRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'startDate' => ['required', 'date_format:Y-m-d'], 'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'weekdays' => ['required', 'array', 'min:1', 'max:7'], 'weekdays.*' => ['required', 'integer', 'between:1,7', 'distinct'],
            'startTime' => ['required', 'date_format:H:i'], 'endTime' => ['required', 'date_format:H:i', 'after:startTime'],
            'title' => ['sometimes', 'nullable', 'string', 'max:200'], 'room' => ['sometimes', 'nullable', 'string', 'max:120'],
            'allowConflicts' => ['sometimes', 'boolean'],
        ];
    }
}
