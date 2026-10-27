<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClassMembership extends Model
{
    use HasUlids;

    protected $attributes = ['version' => 1];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'reviewed_at' => 'datetime', 'joined_at' => 'datetime', 'ended_at' => 'datetime', 'version' => 'integer'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }
}
