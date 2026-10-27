<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AttendanceAudit extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before_data' => 'array', 'after_data' => 'array'];
    }
}
