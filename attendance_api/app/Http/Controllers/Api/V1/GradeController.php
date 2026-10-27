<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Assignments\ManageGrades;
use App\Http\Controllers\Controller;
use App\Http\Requests\GradeRequest;
use App\Http\Requests\PageRequest;
use App\Http\Requests\VersionRequest;
use App\Http\Resources\GradePublicationResource;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\GradePublication;
use App\Models\SubmissionRevision;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class GradeController extends Controller
{
    public function save(GradeRequest $request, string $revisionId, ManageGrades $action): JsonResponse
    {
        $g = $action->save($request->user(), $revisionId, $request->validated());

        return ApiResponse::success(['id' => $g->id, 'revisionId' => $g->revision_id, 'score' => (float) $g->score, 'maxScore' => 10, 'feedback' => $g->feedback, 'state' => $g->state, 'version' => $g->version]);
    }

    public function show(Request $request, string $revisionId): JsonResponse
    {
        $r = SubmissionRevision::with('submission.assignment.classroom')->findOrFail($revisionId);
        Gate::authorize('update', $r->submission->assignment->classroom);
        $g = Grade::where('revision_id', $r->id)->firstOrFail();

        return ApiResponse::success(['id' => $g->id, 'revisionId' => $g->revision_id, 'score' => (float) $g->score, 'maxScore' => 10, 'feedback' => $g->feedback, 'state' => $g->state, 'version' => $g->version]);
    }

    public function publish(VersionRequest $request, string $gradeId, ManageGrades $action): JsonResponse
    {
        [$publication,$created] = $action->publish($request->user(), $gradeId, $request->validated());

        return ApiResponse::success(new GradePublicationResource($publication), $created ? 201 : 200);
    }

    public function index(PageRequest $request, Classroom $classroom): JsonResponse
    {
        Gate::authorize('view', $classroom);
        $query = GradePublication::whereIn('id', DB::table('submissions')->select('published_grade_publication_id')->whereNotNull('published_grade_publication_id'))
            ->whereIn('assignment_id', DB::table('assignments')->select('id')->where('class_id', $classroom->id));
        if ($request->user()->role === 'student') {
            $query->where('student_id', $request->user()->id);
        }
        $data = $request->validated();

        return ApiResponse::page($query->orderBy('assignment_id')->orderBy('id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($g) => new GradePublicationResource($g));
    }

    public function average(Request $request, Classroom $classroom): JsonResponse
    {
        abort_unless($request->user()->role === 'student', 403);
        Gate::authorize('view', $classroom);
        $query = DB::table('submissions as s')->join('assignments as a', 'a.id', '=', 's.assignment_id')
            ->join('grade_publications as p', 'p.id', '=', 's.published_grade_publication_id')
            ->where('a.class_id', $classroom->id)->where('s.student_id', $request->user()->id);
        $row = $query->selectRaw('AVG(p.score) AS average_score,COUNT(p.id) AS graded_count')->first();

        return ApiResponse::success(['average' => $row->graded_count ? round((float) $row->average_score, 2) : null, 'gradedCount' => (int) $row->graded_count, 'maxScore' => 10]);
    }

    public function gradebook(PageRequest $request, Classroom $classroom): JsonResponse
    {
        Gate::authorize('update', $classroom);
        $query = DB::table('class_memberships as m')->join('users as u', 'u.id', '=', 'm.student_id')
            ->leftJoin('submissions as s', fn ($join) => $join->on('s.student_id', '=', 'm.student_id')->whereIn('s.assignment_id', DB::table('assignments')->select('id')->where('class_id', $classroom->id)))
            ->leftJoin('grade_publications as p', 'p.id', '=', 's.published_grade_publication_id')
            ->where('m.class_id', $classroom->id)->whereIn('m.status', ['active', 'left', 'removed'])
            ->select('m.student_id', 'u.display_name', 'm.status')->selectRaw('AVG(p.score) AS average_score,COUNT(p.id) AS graded_count')
            ->groupBy('m.student_id', 'u.display_name', 'm.status')->orderBy('u.display_name')->orderBy('m.student_id');
        $data = $request->validated();
        if (! empty($data['q'])) {
            $query->where('u.display_name', 'like', '%'.addcslashes($data['q'], '%_\\').'%');
        }

        return ApiResponse::page($query->paginate($data['pageSize'] ?? 20, ['m.student_id'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($row) => [
            'studentId' => $row->student_id, 'studentName' => $row->display_name, 'membershipStatus' => $row->status,
            'average' => $row->graded_count ? round((float) $row->average_score, 2) : null, 'gradedCount' => (int) $row->graded_count, 'maxScore' => 10]);
    }
}
