<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class StoredFile extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $hidden = ['storage_key', 'sha256'];

    protected $attributes = ['state' => 'staged'];
}
