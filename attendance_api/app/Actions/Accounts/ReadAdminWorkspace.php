<?php

namespace App\Actions\Accounts;

use App\Models\AdminAccountEvent;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceWindow;
use App\Models\Classroom;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ReadAdminWorkspace
{
    public function summary(): array
    {
        $roles = User::selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        return [
            'users' => User::count(), 'admins' => (int) ($roles['admin'] ?? 0),
            'teachers' => (int) ($roles['teacher'] ?? 0), 'students' => (int) ($roles['student'] ?? 0),
            'lockedAccounts' => User::where('account_status', 'locked')->count(),
            'classes' => Classroom::count(), 'activeClasses' => Classroom::where('status', 'active')->count(),
            'assignments' => Assignment::count(), 'submissions' => Submission::count(),
            'attendanceRecords' => AttendanceRecord::count(),
            'openAttendanceWindows' => AttendanceWindow::where('state', 'open')->where('closes_at', '>', now())->count(),
            'adminEvents' => AdminAccountEvent::count(), 'serverTime' => now()->toISOString(),
        ];
    }

    public function users(array $filters): LengthAwarePaginator
    {
        $query = User::query();
        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        if (isset($filters['status'])) {
            $query->where('account_status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $search = '%'.addcslashes($filters['q'], '%_\\').'%';
            $query->where(fn ($query) => $query->where('display_name', 'like', $search)->orWhere('email', 'like', $search));
        }
        $sort = $filters['sort'] ?? 'name';
        $column = ltrim($sort, '-') === 'name' ? 'display_name' : 'created_at';

        return $query->orderBy($column, str_starts_with($sort, '-') ? 'desc' : 'asc')->orderBy('id')
            ->paginate($filters['pageSize'] ?? 20, ['*'], 'pageNumber', $filters['pageNumber'] ?? 1);
    }

    public function events(array $filters): LengthAwarePaginator
    {
        $query = AdminAccountEvent::with(['actor', 'subject']);
        foreach (['subjectId' => 'subject_id', 'actorId' => 'actor_id', 'operation' => 'operation'] as $key => $column) {
            if (isset($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($filters['pageSize'] ?? 20, ['*'], 'pageNumber', $filters['pageNumber'] ?? 1);
    }
}
