<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ClassInvite extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'expires_at' => 'datetime'];
    }
}
