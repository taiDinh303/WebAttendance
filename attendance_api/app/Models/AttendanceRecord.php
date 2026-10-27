<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AttendanceRecord extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $attributes = ['version' => 1];

    protected function casts(): array
    {
        return ['checked_in_at' => 'immutable_datetime', 'version' => 'integer'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'session_id');
    }
}
