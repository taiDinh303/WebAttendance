<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Submission extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $attributes = ['version' => 1];

    protected function casts(): array
    {
        return ['version' => 'integer'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function latestRevision(): BelongsTo
    {
        return $this->belongsTo(SubmissionRevision::class, 'latest_revision_id');
    }

    public function publishedGrade(): BelongsTo
    {
        return $this->belongsTo(GradePublication::class, 'published_grade_publication_id');
    }
}
