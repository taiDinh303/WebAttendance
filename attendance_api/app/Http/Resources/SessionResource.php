<?php

namespace App\Http\Resources;

use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClassSession */
final class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'classId' => $this->class_id, 'seriesId' => $this->series_id, 'title' => $this->title, 'room' => $this->room,
            'startsAt' => $this->starts_at->toISOString(), 'endsAt' => $this->ends_at->toISOString(),
            'status' => $this->status, 'cancelReason' => $this->cancel_reason, 'version' => $this->version];
    }
}
