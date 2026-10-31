<?php

namespace App\Http\Resources;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Announcement */
final class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'classId' => $this->class_id,
            'state' => $this->state,
            'title' => $this->title,
            'body' => $this->body,
            'pinned' => $this->pinned,
            'hidden' => $this->hidden,
            'expiresAt' => $this->expires_at?->toISOString(),
            'publishedAt' => $this->published_at?->toISOString(),
            'authorId' => $this->author_id,
            'version' => $this->version,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
