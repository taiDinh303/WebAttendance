<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Grade extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $attributes = ['state' => 'draft', 'version' => 1];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'version' => 'integer'];
    }
}
