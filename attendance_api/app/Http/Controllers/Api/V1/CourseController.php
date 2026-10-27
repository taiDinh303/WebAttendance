<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Classes\SaveCourse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Http\Requests\PageRequest;
use App\Http\Requests\VersionRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class CourseController extends Controller
{
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('create', Course::class);
        $data = $request->validated();
        $query = Course::where('owner_id', $request->user()->id);
        if (! empty($data['q'])) {
            $query->where('name', 'like', '%'.addcslashes($data['q'], '%_\\').'%');
        }
        $sort = $data['sort'] ?? 'name';
        $query->orderBy(ltrim($sort, '-') === 'name' ? 'name' : 'updated_at', str_starts_with($sort, '-') ? 'desc' : 'asc')->orderBy('id');

        return ApiResponse::page($query->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($row) => new CourseResource($row));
    }

    public function store(CourseRequest $request, SaveCourse $action): JsonResponse
    {
        $row = $action->handle($request->user(), $request->validated());

        return ApiResponse::success(new CourseResource($row), 201)->header('Location', '/api/v1/courses/'.$row->id);
    }

    public function show(Course $course): JsonResponse
    {
        Gate::authorize('update', $course);

        return ApiResponse::success(new CourseResource($course));
    }

    public function update(CourseRequest $request, Course $course, SaveCourse $action): JsonResponse
    {
        return ApiResponse::success(new CourseResource($action->handle($request->user(), $request->validated(), $course->id)));
    }

    public function destroy(VersionRequest $request, Course $course, SaveCourse $action): Response
    {
        $action->delete($request->user(), $course->id, $request->integer('expectedVersion'));

        return response()->noContent();
    }
}
