<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Course extends Model
{
    use HasUlids;

    protected $attributes = ['version' => 1];

    protected $guarded = ['id', 'owner_id', 'version'];
}
