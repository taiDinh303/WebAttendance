<?php

namespace App\Http\Resources;

use App\Models\AdminAccountEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AdminAccountEvent */
final class AdminAccountEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'actorId' => $this->actor_id, 'actorName' => $this->actor->display_name,
            'subjectId' => $this->subject_id, 'subjectName' => $this->subject->display_name,
            'operation' => $this->operation, 'reason' => $this->reason,
            'before' => $this->before_state, 'after' => $this->after_state, 'createdAt' => $this->created_at->toISOString(),
        ];
    }
}
