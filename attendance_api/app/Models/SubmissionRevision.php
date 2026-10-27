<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SubmissionRevision extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $hidden = ['request_hash', 'idempotency_key'];

    protected function casts(): array
    {
        return ['submitted_at' => 'immutable_datetime', 'is_late' => 'boolean', 'revision_no' => 'integer'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
