<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AnnouncementCommentAudit extends Model
{
    use HasUlids;

    protected $table = 'announcement_comment_audits';

    protected $guarded = ['id', 'comment_id', 'actor_id'];

    protected function casts(): array
    {
        return ['before_data' => 'array', 'after_data' => 'array'];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(AnnouncementComment::class, 'comment_id');
    }
}
