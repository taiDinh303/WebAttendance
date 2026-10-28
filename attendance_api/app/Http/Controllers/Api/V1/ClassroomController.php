<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Classes\ManageClassroom;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassroomRequest;
use App\Http\Requests\PageRequest;
use App\Http\Requests\VersionRequest;
use App\Http\Resources\ClassroomResource;
use App\Models\Classroom;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ClassroomController extends Controller
{
    public function index(PageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $actor = $request->user();
        $query = Classroom::query();
        if ($actor->role === 'teacher') {
            $query->where('owner_id', $actor->id);
        } elseif ($actor->role === 'student') {
            $query->whereHas('memberships', fn ($q) => $q->where('student_id', $actor->id)->where('status', 'active'));
        } else {
            abort(403);
        }
        foreach (['semesterId' => 'semester_id', 'courseId' => 'course_id', 'status' => 'status'] as $field => $column) {
            if (isset($data[$field])) {
                $query->where($column, $data[$field]);
            }
        }
        if (! empty($data['q'])) {
            $query->where('name', 'like', '%'.addcslashes($data['q'], '%_\\').'%');
        }
        $sort = $data['sort'] ?? '-updatedAt';
        $column = ltrim($sort, '-') === 'name' ? 'name' : 'updated_at';
        $query->orderBy($column, str_starts_with($sort, '-') ? 'desc' : 'asc')->orderBy('id');

        return ApiResponse::page($query->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($row) => new ClassroomResource($row));
    }

    public function store(ClassroomRequest $request, ManageClassroom $action): JsonResponse
    {
        [$class, $code] = $action->create($request->user(), $request->validated());

        return ApiResponse::success(['class' => new ClassroomResource($class), 'inviteCode' => $code], 201)->header('Location', '/api/v1/classes/'.$class->id);
    }

    public function show(Request $request, Classroom $classroom): JsonResponse
    {
        Gate::authorize('view', $classroom);

        if ($request->user()->role === 'student') {
            $classroom->load(['memberships' => fn ($query) => $query->where('student_id', $request->user()->id)->where('status', 'active')]);
        }

        return ApiResponse::success(new ClassroomResource($classroom));
    }

    public function update(ClassroomRequest $request, Classroom $classroom, ManageClassroom $action): JsonResponse
    {
        [$class] = $action->mutate($request->user(), $classroom->id, $request->validated(), 'update');

        return ApiResponse::success(new ClassroomResource($class));
    }

    public function archive(VersionRequest $request, Classroom $classroom, ManageClassroom $action): JsonResponse
    {
        [$class] = $action->mutate($request->user(), $classroom->id, $request->validated(), 'archive');

        return ApiResponse::success(new ClassroomResource($class));
    }

    public function rotate(VersionRequest $request, Classroom $classroom, ManageClassroom $action): JsonResponse
    {
        [$class, $code] = $action->mutate($request->user(), $classroom->id, $request->validated(), 'rotate');

        return ApiResponse::success(['class' => new ClassroomResource($class), 'inviteCode' => $code]);
    }

    public function disable(VersionRequest $request, Classroom $classroom, ManageClassroom $action): JsonResponse
    {
        [$class] = $action->mutate($request->user(), $classroom->id, $request->validated(), 'disable');

        return ApiResponse::success(new ClassroomResource($class));
    }
}
