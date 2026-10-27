<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AttendanceWindow extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $attributes = ['state' => 'open', 'token_version' => 1, 'version' => 1];

    protected function casts(): array
    {
        return ['opens_at' => 'immutable_datetime', 'late_after' => 'immutable_datetime', 'closes_at' => 'immutable_datetime', 'token_version' => 'integer', 'version' => 'integer'];
    }
}
