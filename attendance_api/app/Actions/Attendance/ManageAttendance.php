<?php

namespace App\Actions\Attendance;

use App\Exceptions\ApiException;
use App\Models\AttendanceAudit;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRoster;
use App\Models\AttendanceWindow;
use App\Models\ClassMembership;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\User;
use App\Support\QrTokens;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ManageAttendance
{
    public function open(User $actor, Classroom $classroom, string $sessionId, array $data): AttendanceWindow
    {
        return DB::transaction(function () use ($actor, $classroom, $sessionId, $data) {
            [$class, $session] = $this->lockSession($classroom->id, $sessionId);
            Gate::forUser($actor)->authorize('update', $class);
            $this->requireWritable($class, $session);
            $opens = CarbonImmutable::parse($data['opensAt'])->utc()->startOfSecond();
            $closes = CarbonImmutable::parse($data['closesAt'])->utc()->startOfSecond();
            $late = isset($data['lateAfter']) ? CarbonImmutable::parse($data['lateAfter'])->utc()->startOfSecond() : null;
            if ($opens->gt(now()) || $closes->lte(now()) || $closes->lte($opens) || $opens->diffInSeconds($closes) > 86400 || ($late && ($late->lt($opens) || $late->gt($closes)))) {
                throw new ApiException('INVALID_WINDOW', 'The attendance window must contain server time, last at most 24 hours, and contain the late threshold.', 422);
            }
            $existing = AttendanceWindow::where('session_id', $session->id)->lockForUpdate()->first();
            if ($existing) {
                if ($existing->state === 'open' && $existing->opens_at->eq($opens) && $existing->closes_at->eq($closes) && $existing->late_after?->toISOString() === $late?->toISOString()) {
                    return $existing;
                }
                throw new ApiException('STATE_CONFLICT', 'A window already exists. Use manual correction after it closes.');
            }
            $window = AttendanceWindow::create(['session_id' => $session->id, 'opened_by' => $actor->id, 'opens_at' => $opens, 'late_after' => $late, 'closes_at' => $closes]);
            $students = ClassMembership::where('class_id', $class->id)->where('status', 'active')
                ->whereHas('student', fn ($q) => $q->where('account_status', 'active'))->pluck('student_id');
            foreach ($students as $studentId) {
                AttendanceRoster::create(['window_id' => $window->id, 'student_id' => $studentId]);
            }

            return $window;
        });
    }

    public function qr(User $actor, string $windowId, QrTokens $tokens): array
    {
        $candidate = AttendanceWindow::findOrFail($windowId);
        $session = ClassSession::findOrFail($candidate->session_id);
        Gate::forUser($actor)->authorize('update', Classroom::findOrFail($session->class_id));

        return DB::transaction(function () use ($actor, $candidate, $tokens) {
            $session = ClassSession::findOrFail($candidate->session_id);
            [$class, $lockedSession] = $this->lockSession($session->class_id, $session->id);
            Gate::forUser($actor)->authorize('update', $class);
            $this->requireWritable($class, $lockedSession);
            $window = AttendanceWindow::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            $this->requireOpen($window);

            return $tokens->issue($window);
        });
    }

    public function checkIn(User $actor, string $token, QrTokens $tokens): array
    {
        abort_unless($actor->role === 'student', 403);
        $claims = $tokens->read($token);
        $window = AttendanceWindow::findOrFail($claims['windowId']);
        $session = ClassSession::findOrFail($window->session_id);

        return DB::transaction(function () use ($actor, $claims, $window, $session) {
            [$class, $lockedSession] = $this->lockSession($session->class_id, $session->id);
            Gate::forUser($actor)->authorize('view', $class);
            $this->requireWritable($class, $lockedSession);
            $locked = AttendanceWindow::whereKey($window->id)->lockForUpdate()->firstOrFail();
            if (! AttendanceRoster::where('window_id', $locked->id)->where('student_id', $actor->id)->exists()) {
                throw new ApiException('NOT_IN_ATTENDANCE_ROSTER', 'You were not in the roster when this window opened.', 403);
            }
            $this->requireOpen($locked);
            if ($claims['version'] !== $locked->token_version || now()->getTimestamp() >= $claims['expiresAt'] || now()->getTimestamp() < $claims['issuedAt']) {
                throw new ApiException('QR_EXPIRED', 'Scan the current QR code.', 410);
            }
            $existing = AttendanceRecord::where('session_id', $lockedSession->id)->where('student_id', $actor->id)->first();
            if ($existing) {
                return [$existing, false];
            }
            $record = AttendanceRecord::create(['session_id' => $lockedSession->id, 'student_id' => $actor->id, 'window_id' => $locked->id,
                'status' => $locked->late_after && now()->gte($locked->late_after) ? 'late' : 'present',
                'checked_in_at' => now()->startOfSecond(), 'recorded_source' => 'qr']);
            $this->audit($record, $actor->id, 'qr_check_in', null, null);

            return [$record, true];
        });
    }

    public function close(User $actor, string $windowId, int $version): AttendanceWindow
    {
        $candidate = AttendanceWindow::findOrFail($windowId);
        $session = ClassSession::findOrFail($candidate->session_id);

        return DB::transaction(function () use ($actor, $candidate, $session, $version) {
            [$class] = $this->lockSession($session->class_id, $session->id);
            Gate::forUser($actor)->authorize('update', $class);
            $window = AttendanceWindow::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            if ($window->state !== 'open') {
                return $window;
            }
            if ($window->version !== $version) {
                throw new ApiException('VERSION_CONFLICT', 'Attendance window changed.');
            }
            $this->finalizeLocked($window, 'closed');

            return $window;
        });
    }

    public function correct(User $actor, Classroom $classroom, string $sessionId, string $studentId, array $data): AttendanceRecord
    {
        return DB::transaction(function () use ($actor, $classroom, $sessionId, $studentId, $data) {
            [$class, $session] = $this->lockSession($classroom->id, $sessionId);
            Gate::forUser($actor)->authorize('update', $class);
            $this->requireWritable($class, $session);
            $window = AttendanceWindow::where('session_id', $session->id)->lockForUpdate()->firstOrFail();
            if (! AttendanceRoster::where('window_id', $window->id)->where('student_id', $studentId)->exists()) {
                abort(404);
            }
            $record = AttendanceRecord::where('session_id', $session->id)->where('student_id', $studentId)->lockForUpdate()->first();
            if (($record?->version ?? 0) !== (int) $data['expectedVersion']) {
                throw new ApiException('VERSION_CONFLICT', 'Attendance changed. Reload before saving.');
            }
            $before = $record ? $this->snapshot($record) : null;
            $record ??= new AttendanceRecord(['session_id' => $session->id, 'student_id' => $studentId, 'window_id' => $window->id]);
            if ($record->exists) {
                $record->version++;
            }
            $record->status = $data['status'];
            $record->recorded_source = 'manual';
            $record->save();
            $this->audit($record, $actor->id, 'manual_correction', $data['reason'], $before);

            return $record;
        });
    }

    public function finalizeAllDue(?string $classId = null, ?string $studentId = null): int
    {
        $count = 0;
        do {
            $batch = $this->finalizeDue($classId, $studentId);
            $count += $batch;
        } while ($batch === 100);

        return $count;
    }

    public function finalizeDue(?string $classId = null, ?string $studentId = null): int
    {
        $query = AttendanceWindow::where('state', 'open')->where('closes_at', '<=', now())
            ->when($classId, fn ($q) => $q->whereIn('session_id', ClassSession::select('id')->where('class_id', $classId)))
            ->when($studentId, fn ($q) => $q->whereIn('id', AttendanceRoster::select('window_id')->where('student_id', $studentId)));
        $count = 0;
        foreach ($query->orderBy('id')->limit(100)->get() as $candidate) {
            DB::transaction(function () use ($candidate, &$count) {
                $session = ClassSession::findOrFail($candidate->session_id);
                $this->lockSession($session->class_id, $session->id);
                $window = AttendanceWindow::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                if ($window->state === 'open' && now()->gte($window->closes_at)) {
                    $this->finalizeLocked($window, 'expired');
                    $count++;
                }
            });
        }

        return $count;
    }

    public function finalizeLocked(AttendanceWindow $window, string $state): void
    {
        $session = ClassSession::findOrFail($window->session_id);
        if ($session->status !== 'cancelled') {
            foreach (AttendanceRoster::where('window_id', $window->id)->pluck('student_id') as $studentId) {
                if (! AttendanceRecord::where('session_id', $session->id)->where('student_id', $studentId)->exists()) {
                    $record = AttendanceRecord::create(['session_id' => $session->id, 'student_id' => $studentId, 'window_id' => $window->id, 'status' => 'absent', 'recorded_source' => 'close_finalize']);
                    $this->audit($record, null, 'auto_absent', null, null);
                }
            }
        }
        $window->state = $state;
        $window->token_version++;
        $window->version++;
        $window->save();
    }

    private function lockSession(string $classId, string $sessionId): array
    {
        $class = Classroom::whereKey($classId)->lockForUpdate()->firstOrFail();
        $session = ClassSession::where('class_id', $classId)->whereKey($sessionId)->lockForUpdate()->firstOrFail();

        return [$class, $session];
    }

    private function requireWritable(Classroom $class, ClassSession $session): void
    {
        if ($class->status !== 'active' || $session->status === 'cancelled') {
            throw new ApiException('STATE_CONFLICT', 'The class is archived or the session is cancelled.');
        }
    }

    private function requireOpen(AttendanceWindow $window): void
    {
        if (now()->gte($window->closes_at)) {
            throw new ApiException('QR_EXPIRED', 'The attendance window has expired.', 410);
        }
        if ($window->state !== 'open' || now()->lt($window->opens_at)) {
            throw new ApiException('WINDOW_CLOSED', 'The attendance window is closed.', 409);
        }
    }

    private function snapshot(AttendanceRecord $record): array
    {
        return ['status' => $record->status, 'recordedSource' => $record->recorded_source, 'checkedInAt' => $record->checked_in_at?->toISOString(), 'version' => $record->version];
    }

    private function audit(AttendanceRecord $record, ?string $actorId, string $action, ?string $reason, ?array $before): void
    {
        AttendanceAudit::create(['record_id' => $record->id, 'actor_id' => $actorId, 'action' => $action, 'reason' => $reason, 'before_data' => $before, 'after_data' => $this->snapshot($record)]);
    }
}
