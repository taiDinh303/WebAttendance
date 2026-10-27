<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Attendance\ManageAttendance;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Http\Requests\AttendanceListRequest;
use App\Http\Requests\AttendanceWindowRequest;
use App\Http\Requests\CheckInRequest;
use App\Http\Requests\PageRequest;
use App\Http\Requests\VersionRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Http\Resources\AttendanceWindowResource;
use App\Models\AttendanceAudit;
use App\Models\AttendanceRecord;
use App\Models\AttendanceWindow;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Support\ApiResponse;
use App\Support\QrTokens;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class AttendanceController extends Controller
{
    public function open(AttendanceWindowRequest $request, Classroom $classroom, string $sessionId, ManageAttendance $action): JsonResponse
    {
        return ApiResponse::success(new AttendanceWindowResource($action->open($request->user(), $classroom, $sessionId, $request->validated())), 201);
    }

    public function window(Request $request, Classroom $classroom, string $sessionId, ManageAttendance $action): JsonResponse
    {
        Gate::authorize('update', $classroom);
        $session = ClassSession::where('class_id', $classroom->id)->whereKey($sessionId)->firstOrFail();
        $action->finalizeAllDue($classroom->id);

        return ApiResponse::success(new AttendanceWindowResource(AttendanceWindow::where('session_id', $session->id)->firstOrFail()));
    }

    public function roster(PageRequest $request, string $windowId, ManageAttendance $action): JsonResponse
    {
        $window = AttendanceWindow::findOrFail($windowId);
        $session = ClassSession::findOrFail($window->session_id);
        Gate::authorize('update', Classroom::findOrFail($session->class_id));
        $action->finalizeAllDue($session->class_id);
        $data = $request->validated();
        $query = DB::table('attendance_rosters as r')->join('users as u', 'u.id', '=', 'r.student_id')
            ->leftJoin('attendance_records as a', fn ($join) => $join->on('a.student_id', '=', 'r.student_id')->where('a.session_id', $session->id))
            ->where('r.window_id', $window->id)->select('r.student_id', 'u.display_name', 'a.id as record_id', 'a.status', 'a.version');
        if (! empty($data['q'])) {
            $query->where('u.display_name', 'like', '%'.addcslashes($data['q'], '%_\\').'%');
        }

        return ApiResponse::page($query->orderBy('r.student_id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($row) => [
            'studentId' => $row->student_id, 'studentName' => $row->display_name, 'windowId' => $window->id,
            'recordId' => $row->record_id, 'status' => $row->status ?? 'unmarked', 'recordVersion' => (int) ($row->version ?? 0),
        ]);
    }

    public function qr(Request $request, string $windowId, ManageAttendance $action, QrTokens $tokens): JsonResponse
    {
        return ApiResponse::success($action->qr($request->user(), $windowId, $tokens));
    }

    public function close(VersionRequest $request, string $windowId, ManageAttendance $action): JsonResponse
    {
        return ApiResponse::success(new AttendanceWindowResource($action->close($request->user(), $windowId, $request->integer('expectedVersion'))));
    }

    public function checkIn(CheckInRequest $request, ManageAttendance $action, QrTokens $tokens): JsonResponse
    {
        [$record, $created] = $action->checkIn($request->user(), $request->validated('qrToken'), $tokens);

        return ApiResponse::success(new AttendanceRecordResource($record), $created ? 201 : 200);
    }

    public function correct(AttendanceCorrectionRequest $request, Classroom $classroom, string $sessionId, string $studentId, ManageAttendance $action): JsonResponse
    {
        return ApiResponse::success(new AttendanceRecordResource($action->correct($request->user(), $classroom, $sessionId, $studentId, $request->validated())));
    }

    public function index(AttendanceListRequest $request, Classroom $classroom, ManageAttendance $action): JsonResponse
    {
        Gate::authorize('update', $classroom);
        $action->finalizeAllDue($classroom->id);

        return $this->records($request, AttendanceRecord::whereHas('session', fn ($q) => $q->where('class_id', $classroom->id)), false);
    }

    public function mine(AttendanceListRequest $request, ManageAttendance $action): JsonResponse
    {
        abort_unless($request->user()->role === 'student', 403);
        $action->finalizeAllDue(null, $request->user()->id);
        $query = AttendanceRecord::where('student_id', $request->user()->id)
            ->whereHas('session.classroom.memberships', fn ($q) => $q->where('student_id', $request->user()->id)->where('status', 'active'));

        return $this->records($request, $query, true);
    }

    public function audit(PageRequest $request, Classroom $classroom, string $recordId): JsonResponse
    {
        Gate::authorize('update', $classroom);
        $record = AttendanceRecord::whereHas('session', fn ($q) => $q->where('class_id', $classroom->id))->whereKey($recordId)->firstOrFail();
        $data = $request->validated();

        return ApiResponse::page(AttendanceAudit::where('record_id', $record->id)->orderBy('created_at')->orderBy('id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($audit) => [
            'id' => $audit->id, 'actorId' => $audit->actor_id, 'action' => $audit->action, 'reason' => $audit->reason,
            'before' => $audit->before_data, 'after' => $audit->after_data, 'createdAt' => $audit->created_at->toISOString(),
        ]);
    }

    public function summary(AttendanceListRequest $request, Classroom $classroom, ManageAttendance $action): JsonResponse
    {
        Gate::authorize('update', $classroom);
        $action->finalizeAllDue($classroom->id);
        $data = $request->validated();
        if (isset($data['status'])) {
            throw new ApiException('UNSUPPORTED_FILTER', 'Status filtering is not supported for attendance totals.', 422);
        }
        $query = DB::table('attendance_records as r')
            ->join('class_sessions as s', 's.id', '=', 'r.session_id')
            ->join('attendance_windows as w', 'w.id', '=', 'r.window_id')
            ->where('s.class_id', $classroom->id)->where('s.status', '!=', 'cancelled')
            ->where(fn ($q) => $q->where('w.state', '!=', 'open')->orWhere('w.closes_at', '<=', now()));
        if (isset($data['studentId'])) {
            $query->where('r.student_id', $data['studentId']);
        }
        if (isset($data['sessionId'])) {
            $query->where('s.id', $data['sessionId']);
        }
        if (isset($data['from'], $data['to'])) {
            $from = CarbonImmutable::parse($data['from'])->utc();
            $to = CarbonImmutable::parse($data['to'])->utc();
            if ($from->diffInDays($to) > 366) {
                throw new ApiException('RANGE_TOO_LARGE', 'Attendance range may not exceed 366 days.', 422);
            }
            $query->where('s.starts_at', '>=', $from)->where('s.starts_at', '<', $to);
        }
        $query->select('r.student_id')->selectRaw("SUM(r.status = 'present') AS present_count, SUM(r.status = 'late') AS late_count, SUM(r.status = 'absent') AS absent_count, COUNT(*) AS total")->groupBy('r.student_id')->orderBy('r.student_id');

        return ApiResponse::page($query->paginate($data['pageSize'] ?? 20, ['r.student_id'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($row) => [
            'studentId' => $row->student_id, 'present' => (int) $row->present_count, 'late' => (int) $row->late_count,
            'absent' => (int) $row->absent_count, 'total' => (int) $row->total, 'attendanceRate' => $row->total ? round(((int) $row->present_count + (int) $row->late_count) / (int) $row->total, 4) : null,
        ]);
    }

    private function records(AttendanceListRequest $request, Builder $query, bool $self): JsonResponse
    {
        $data = $request->validated();
        if ($self && isset($data['studentId']) && $data['studentId'] !== $request->user()->id) {
            abort(403);
        }
        foreach (['sessionId' => 'session_id', 'status' => 'status'] as $field => $column) {
            if (isset($data[$field])) {
                $query->where($column, $data[$field]);
            }
        }
        if (! $self && isset($data['studentId'])) {
            $query->where('student_id', $data['studentId']);
        }
        if (isset($data['classId'])) {
            $query->whereHas('session', fn ($q) => $q->where('class_id', $data['classId']));
        }
        if (isset($data['from'], $data['to'])) {
            $from = CarbonImmutable::parse($data['from'])->utc();
            $to = CarbonImmutable::parse($data['to'])->utc();
            if ($from->diffInDays($to) > 366) {
                throw new ApiException('RANGE_TOO_LARGE', 'Attendance range may not exceed 366 days.', 422);
            }
            $query->whereHas('session', fn ($q) => $q->where('starts_at', '>=', $from)->where('starts_at', '<', $to));
        }

        return ApiResponse::page($query->with('session')->orderByDesc('created_at')->orderBy('id')->paginate($data['pageSize'] ?? 20, ['*'], 'pageNumber', $data['pageNumber'] ?? 1), fn ($record) => new AttendanceRecordResource($record));
    }
}
