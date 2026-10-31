<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Announcement extends Model
{
    use HasUlids;

    protected $guarded = ['id', 'class_id', 'author_id', 'state', 'published_at', 'version'];

    protected $attributes = ['state' => 'draft', 'pinned' => false, 'hidden' => false, 'version' => 1];

    protected function casts(): array
    {
        return ['pinned' => 'boolean', 'hidden' => 'boolean', 'version' => 'integer', 'expires_at' => 'immutable_datetime', 'published_at' => 'immutable_datetime'];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AnnouncementComment::class);
    }
}
