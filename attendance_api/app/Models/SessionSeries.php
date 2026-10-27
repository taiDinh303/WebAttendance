<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SessionSeries extends Model
{
    use HasUlids;

    protected $table = 'session_series';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['definition' => 'array'];
    }
}
