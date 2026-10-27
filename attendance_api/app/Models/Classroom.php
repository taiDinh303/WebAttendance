<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Classroom extends Model
{
    use HasUlids;

    protected $table = 'classes';

    protected $attributes = ['status' => 'active', 'version' => 1, 'timezone' => 'Asia/Ho_Chi_Minh'];

    protected $guarded = ['id', 'owner_id', 'status', 'archived_at', 'version'];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime', 'version' => 'integer'];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ClassMembership::class, 'class_id');
    }
}
