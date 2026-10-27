<?php

namespace App\Http\Resources;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Submission */
final class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'assignmentId' => $this->assignment_id, 'studentId' => $this->student_id,
            'studentName' => $this->whenLoaded('student', fn () => $this->student->display_name), 'latestRevisionId' => $this->latest_revision_id,
            'version' => $this->version, 'publishedGrade' => $this->whenLoaded('publishedGrade', fn () => $this->publishedGrade ? new GradePublicationResource($this->publishedGrade) : null)];
    }
}
