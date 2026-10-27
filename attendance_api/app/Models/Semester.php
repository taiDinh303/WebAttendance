<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Semester extends Model
{
    use HasUlids;

    protected $attributes = ['version' => 1, 'is_active' => true];

    protected $guarded = ['id', 'owner_id', 'version'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_active' => 'boolean', 'version' => 'integer'];
    }
}
