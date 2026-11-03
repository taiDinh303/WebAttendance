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
        return ['approvedAt' => $this->approved_at?->toISOString(), 'approvedBy' => $this->approved_by, 'studentCode' => $this->whenLoaded('student', fn () => $this->student->student_code), 'id' => $this->id, 'sessionId' => $this->session_id, 'studentId' => $this->student_id, 'windowId' => $this->window_id,
            'status' => $this->status, 'checkedInAt' => $this->checked_in_at?->toISOString(), 'recordedSource' => $this->recorded_source, 'version' => $this->version,
            'studentName' => $this->whenLoaded('student', fn () => $this->student->display_name),
            'sessionTitle' => $this->whenLoaded('session', fn () => $this->session->title),
            'sessionStartsAt' => $this->whenLoaded('session', fn () => $this->session->starts_at->toISOString()),
            'sessionStatus' => $this->whenLoaded('session', fn () => $this->session->status)];
    }
}
