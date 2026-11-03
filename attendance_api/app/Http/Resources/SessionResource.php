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
        $closed = $this->review_window_state && ($this->review_window_state !== 'open' || ($this->review_closes_at && now()->gte($this->review_closes_at)));
        $reviewed = $closed && $this->review_record_count > 0 && (int) $this->review_pending_count === 0 && $this->review_record_count >= $this->review_roster_count;

        return ['attendanceReviewState' => $reviewed ? 'approved' : 'pending', 'id' => $this->id, 'classId' => $this->class_id, 'seriesId' => $this->series_id, 'title' => $this->title, 'room' => $this->room,
            'startsAt' => $this->starts_at->toISOString(), 'endsAt' => $this->ends_at->toISOString(),
            'status' => $this->status, 'cancelReason' => $this->cancel_reason, 'version' => $this->version];
    }
}
