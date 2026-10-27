<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Assignment extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $attributes = ['state' => 'draft', 'version' => 1];

    protected function casts(): array
    {
        return ['due_at' => 'immutable_datetime', 'allow_late' => 'boolean', 'allow_resubmit' => 'boolean', 'version' => 'integer'];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }
}
