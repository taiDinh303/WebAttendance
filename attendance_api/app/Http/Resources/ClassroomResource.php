<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ClassroomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'description' => $this->description,
            'ownerId' => $this->owner_id, 'semesterId' => $this->semester_id, 'courseId' => $this->course_id,
            'timezone' => $this->timezone, 'status' => $this->status, 'version' => $this->version,
            'membershipVersion' => $this->whenLoaded('memberships', fn () => $this->memberships->first()?->version),
            'createdAt' => $this->created_at?->toISOString(), 'updatedAt' => $this->updated_at?->toISOString()];
    }
}
