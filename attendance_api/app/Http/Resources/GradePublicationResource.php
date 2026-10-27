<?php

namespace App\Http\Resources;

use App\Models\GradePublication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GradePublication */
final class GradePublicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'assignmentId' => $this->assignment_id, 'submissionId' => $this->submission_id, 'revisionId' => $this->revision_id,
            'studentId' => $this->student_id, 'score' => (float) $this->score, 'maxScore' => 10, 'feedback' => $this->feedback, 'publishedAt' => $this->published_at->toISOString()];
    }
}
