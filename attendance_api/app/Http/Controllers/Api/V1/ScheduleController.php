<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Schedule\ManageSchedule;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CalendarRequest;
use App\Http\Requests\SeriesRequest;
use App\Http\Requests\SessionRequest;
use App\Http\Resources\SessionResource;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ScheduleController extends Controller
{
    public function calendar(CalendarRequest $request): JsonResponse
    {
        $data = $request->validated();
        $query = ClassSession::query();
        $user = $request->user();
        if ($user->role === 'teacher') {
            $query->whereHas('classroom', fn ($q) => $q->where('owner_id', $user->id));
        } elseif ($user->role === 'student') {
            $query->whereHas('classroom.memberships', fn ($q) => $q->where('student_id', $user->id)->where('status', 'active'));
        } else {
            abort(403);
        }
        if (isset($data['classId'])) {
            $query->where('class_id', $data['classId']);
        }

        return $this->page($request, $query);
    }

    public function index(CalendarRequest $request, Classroom $classroom): JsonResponse
    {
        Gate::authorize('view', $classroom);

        return $this->page($request, ClassSession::where('class_id', $classroom->id));
    }

    public function show(Request $request, Classroom $classroom, string $sessionId): JsonResponse
    {
        Gate::authorize('view', $classroom);

        return ApiResponse::success(new SessionResource(ClassSession::where('class_id', $classroom->id)->whereKey($sessionId)->firstOrFail()));
    }

    public function store(SessionRequest $request, Classroom $classroom, ManageSchedule $action): JsonResponse
    {
        $session = $action->create($request->user(), $classroom, $request->validated());

        return ApiResponse::success(new SessionResource($session), 201)->header('Location', '/api/v1/classes/'.$classroom->id.'/sessions/'.$session->id);
    }

    public function update(SessionRequest $request, Classroom $classroom, string $sessionId, ManageSchedule $action): JsonResponse
    {
        return ApiResponse::success(new SessionResource($action->update($request->user(), $classroom, $sessionId, $request->validated())));
    }

    public function cancel(Request $request, Classroom $classroom, string $sessionId, ManageSchedule $action): JsonResponse
    {
        $data = $request->validate(['expectedVersion' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:3', 'max:500']]);

        return ApiResponse::success(new SessionResource($action->cancel($request->user(), $classroom, $sessionId, $data)));
    }

    public function preview(SeriesRequest $request, Classroom $classroom, ManageSchedule $action): JsonResponse
    {
        return ApiResponse::success($action->preview($request->user(), $classroom, $request->validated()));
    }

    public function series(SeriesRequest $request, Classroom $classroom, ManageSchedule $action): JsonResponse
    {
        return ApiResponse::success($action->createSeries($request->user(), $classroom, $request->validated()), 201);
    }

    private function page(CalendarRequest $request, Builder $query): JsonResponse
    {
        $data = $request->validated();
        $from = CarbonImmutable::parse($data['from'])->utc();
        $to = CarbonImmutable::parse($data['to'])->utc();
        if ($from->diffInDays($to) > 93) {
            throw new ApiException('RANGE_TOO_LARGE', 'Calendar range may not exceed 93 days.', 422);
        }
        $query->where('starts_at', '<', $to)->where('ends_at', '>', $from);
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        return ApiResponse::page($query->orderBy('starts_at')->orderBy('id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($row) => new SessionResource($row));
    }
}
