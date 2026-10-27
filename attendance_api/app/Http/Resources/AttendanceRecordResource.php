<?php

namespace App\Http\Resources;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttendanceRecord */
final class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'sessionId' => $this->session_id, 'studentId' => $this->student_id, 'windowId' => $this->window_id,
            'status' => $this->status, 'checkedInAt' => $this->checked_in_at?->toISOString(), 'recordedSource' => $this->recorded_source, 'version' => $this->version,
            'sessionStatus' => $this->whenLoaded('session', fn () => $this->session->status)];
    }
}
