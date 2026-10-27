<?php

namespace App\Http\Resources;

use App\Models\ClassMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClassMembership */
final class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'classId' => $this->class_id, 'studentId' => $this->student_id,
            'studentName' => $this->whenLoaded('student', fn () => $this->student->display_name),
            'className' => $this->whenLoaded('classroom', fn () => $this->classroom->name),
            'status' => $this->status, 'requestedAt' => $this->requested_at?->toISOString(),
            'reviewedAt' => $this->reviewed_at?->toISOString(), 'reviewNote' => $this->review_note,
            'joinedAt' => $this->joined_at?->toISOString(), 'endedAt' => $this->ended_at?->toISOString(), 'version' => $this->version];
    }
}
