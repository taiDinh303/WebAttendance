<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Assignments\ManageSubmissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Http\Requests\SubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Submission;
use App\Models\SubmissionRevision;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SubmissionController extends Controller
{
    public function store(SubmissionRequest $request, Classroom $classroom, string $assignmentId, ManageSubmissions $action): JsonResponse
    {
        [$s,$r,$created] = $action->submit($request->user(), $classroom, $assignmentId, $request->validated());

        return ApiResponse::success(['submission' => new SubmissionResource($s->load('publishedGrade')), 'revision' => $this->revision($r)], $created ? 201 : 200);
    }

    public function index(PageRequest $request, Classroom $classroom, string $assignmentId): JsonResponse
    {
        Gate::authorize('update', $classroom);
        Assignment::where('class_id', $classroom->id)->whereKey($assignmentId)->firstOrFail();

        return $this->listing($request, Submission::where('assignment_id', $assignmentId));
    }

    public function mine(PageRequest $request): JsonResponse
    {
        abort_unless($request->user()->role === 'student', 403);
        $query = Submission::where('student_id', $request->user()->id)->whereHas('assignment.classroom.memberships', fn ($q) => $q->where('student_id', $request->user()->id)->where('status', 'active'));
        if ($request->filled('assignmentId')) {
            $query->where('assignment_id', $request->validated('assignmentId'));
        }

        return $this->listing($request, $query);
    }

    public function show(PageRequest $request, string $submissionId): JsonResponse
    {
        $s = $this->authorizeSubmission($request, $submissionId);
        $data = $request->validated();
        $page = SubmissionRevision::where('submission_id', $s->id)->orderByDesc('revision_no')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1);
        $ids = $page->getCollection()->pluck('id');
        $files = DB::table('submission_files as l')->join('stored_files as f', 'f.id', '=', 'l.file_id')->whereIn('l.revision_id', $ids)->select('l.revision_id', 'f.id', 'f.original_name', 'f.mime_type', 'f.size_bytes')->get()->groupBy('revision_id');

        return ApiResponse::page($page, fn ($r) => [...$this->revision($r), 'files' => ($files->get($r->id) ?? collect())->map(fn ($f) => ['id' => $f->id, 'originalName' => $f->original_name, 'mimeType' => $f->mime_type, 'sizeBytes' => (int) $f->size_bytes])]);
    }

    public function gradeEvents(PageRequest $request, string $submissionId): JsonResponse
    {
        $s = Submission::with('assignment.classroom')->findOrFail($submissionId);
        Gate::authorize('update', $s->assignment->classroom);
        $data = $request->validated();

        return ApiResponse::page(DB::table('grade_events')->where('submission_id', $s->id)->orderBy('created_at')->orderBy('id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1),
            fn ($e) => ['id' => $e->id, 'actorId' => $e->actor_id, 'beforePublicationId' => $e->before_publication_id, 'afterPublicationId' => $e->after_publication_id, 'reason' => $e->reason, 'createdAt' => CarbonImmutable::parse($e->created_at, 'UTC')->toISOString()]);
    }

    private function authorizeSubmission(PageRequest $request, string $id): Submission
    {
        $s = Submission::with('assignment.classroom')->findOrFail($id);
        Gate::authorize('view', $s->assignment->classroom);
        if ($request->user()->role === 'student') {
            abort_unless($s->student_id === $request->user()->id, 404);
        }

        return $s;
    }

    private function listing(PageRequest $request, Builder $query): JsonResponse
    {
        $data = $request->validated();

        return ApiResponse::page($query->with(['publishedGrade', 'student'])->orderByDesc('updated_at')->orderBy('id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($s) => new SubmissionResource($s));
    }

    private function revision(SubmissionRevision $r): array
    {
        return ['id' => $r->id, 'submissionId' => $r->submission_id, 'revisionNo' => $r->revision_no, 'note' => $r->note, 'submittedAt' => $r->submitted_at->toISOString(), 'isLate' => $r->is_late];
    }
}
