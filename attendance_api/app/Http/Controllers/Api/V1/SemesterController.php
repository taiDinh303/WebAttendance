<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Classes\SaveSemester;
use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Http\Requests\SemesterRequest;
use App\Http\Requests\VersionRequest;
use App\Http\Resources\SemesterResource;
use App\Models\Semester;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class SemesterController extends Controller
{
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('create', Semester::class);
        $data = $request->validated();
        $query = Semester::where('owner_id', $request->user()->id);
        if (! empty($data['q'])) {
            $query->where('name', 'like', '%'.addcslashes($data['q'], '%_\\').'%');
        }
        $sort = $data['sort'] ?? 'name';
        $query->orderBy(ltrim($sort, '-') === 'name' ? 'name' : 'updated_at', str_starts_with($sort, '-') ? 'desc' : 'asc')->orderBy('id');

        return ApiResponse::page($query->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($row) => new SemesterResource($row));
    }

    public function store(SemesterRequest $request, SaveSemester $action): JsonResponse
    {
        $row = $action->handle($request->user(), $request->validated());

        return ApiResponse::success(new SemesterResource($row), 201)->header('Location', '/api/v1/semesters/'.$row->id);
    }

    public function show(Semester $semester): JsonResponse
    {
        Gate::authorize('update', $semester);

        return ApiResponse::success(new SemesterResource($semester));
    }

    public function update(SemesterRequest $request, Semester $semester, SaveSemester $action): JsonResponse
    {
        return ApiResponse::success(new SemesterResource($action->handle($request->user(), $request->validated(), $semester->id)));
    }

    public function destroy(VersionRequest $request, Semester $semester, SaveSemester $action): Response
    {
        $action->delete($request->user(), $semester->id, $request->integer('expectedVersion'));

        return response()->noContent();
    }
}
