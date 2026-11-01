<?php

namespace App\Http\Resources;

use App\Models\AnnouncementComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AnnouncementComment */
final class AnnouncementCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'announcementId' => $this->announcement_id,
            'authorId' => $this->author_id,
            'body' => $request->user()?->role === 'student' && ($this->hidden || $this->deleted_at !== null) ? null : $this->body,
            'hidden' => $this->hidden,
            'deletedAt' => $this->deleted_at?->toISOString(),
            'version' => $this->version,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
