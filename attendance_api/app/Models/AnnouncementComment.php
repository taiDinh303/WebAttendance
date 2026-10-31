<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AnnouncementComment extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = ['id', 'announcement_id', 'author_id', 'version'];

    protected $attributes = ['hidden' => false, 'version' => 1];

    protected function casts(): array
    {
        return ['hidden' => 'boolean', 'version' => 'integer', 'deleted_at' => 'datetime'];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id');
    }
}
