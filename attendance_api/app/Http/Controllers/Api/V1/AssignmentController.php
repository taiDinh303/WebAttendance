<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Assignments\ManageAssignments;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignmentRequest;
use App\Http\Requests\PageRequest;
use App\Http\Requests\VersionRequest;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class AssignmentController extends Controller
{
    public function index(PageRequest $request, Classroom $classroom): JsonResponse
    {
        Gate::authorize('view', $classroom);
        $query = Assignment::where('class_id', $classroom->id);
        if ($request->user()->role === 'student') {
            $query->whereIn('state', ['published', 'closed']);
        }
        $data = $request->validated();
        if (! empty($data['q'])) {
            $query->where('title', 'like', '%'.addcslashes($data['q'], '%_\\').'%');
        }

        return ApiResponse::page($query->orderByDesc('created_at')->orderBy('id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($a) => new AssignmentResource($a));
    }

    public function store(AssignmentRequest $request, Classroom $classroom, ManageAssignments $action): JsonResponse
    {
        $a = $action->save($request->user(), $classroom, $request->validated());

        return ApiResponse::success(new AssignmentResource($a), 201)->header('Location', '/api/v1/classes/'.$classroom->id.'/assignments/'.$a->id);
    }

    public function show(Request $request, Classroom $classroom, string $assignmentId): JsonResponse
    {
        Gate::authorize('view', $classroom);
        $query = Assignment::where('class_id', $classroom->id)->whereKey($assignmentId);
        if ($request->user()->role === 'student') {
            $query->whereIn('state', ['published', 'closed']);
        }
        $a = $query->firstOrFail();
        $files = DB::table('assignment_files as l')->join('stored_files as f', 'f.id', '=', 'l.file_id')->where('l.assignment_id', $a->id)
            ->where('f.state', 'attached')->select('f.id', 'f.original_name', 'f.mime_type', 'f.size_bytes')->orderBy('f.id')->get();

        return ApiResponse::success(['assignment' => new AssignmentResource($a), 'files' => $files->map(fn ($f) => ['id' => $f->id, 'originalName' => $f->original_name, 'mimeType' => $f->mime_type, 'sizeBytes' => (int) $f->size_bytes])]);
    }

    public function update(AssignmentRequest $request, Classroom $classroom, string $assignmentId, ManageAssignments $action): JsonResponse
    {
        return ApiResponse::success(new AssignmentResource($action->save($request->user(), $classroom, $request->validated(), $assignmentId)));
    }

    public function publish(VersionRequest $request, Classroom $classroom, string $assignmentId, ManageAssignments $action): JsonResponse
    {
        return ApiResponse::success(new AssignmentResource($action->transition($request->user(), $classroom, $assignmentId, $request->integer('expectedVersion'), 'published')));
    }

    public function close(VersionRequest $request, Classroom $classroom, string $assignmentId, ManageAssignments $action): JsonResponse
    {
        return ApiResponse::success(new AssignmentResource($action->transition($request->user(), $classroom, $assignmentId, $request->integer('expectedVersion'), 'closed')));
    }
}
