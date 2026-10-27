<?php

namespace App\Http\Resources;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Assignment */
final class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'classId' => $this->class_id, 'title' => $this->title, 'instructions' => $this->instructions, 'maxScore' => 10,
            'dueAt' => $this->due_at?->toISOString(), 'allowLate' => $this->allow_late, 'allowResubmit' => $this->allow_resubmit, 'state' => $this->state, 'version' => $this->version];
    }
}
