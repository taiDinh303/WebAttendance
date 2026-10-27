<?php

namespace App\Actions\Schedule;

use App\Exceptions\ApiException;
use App\Models\AttendanceWindow;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\SessionSeries;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ManageSchedule
{
    public function create(User $actor, Classroom $classroom, array $data): ClassSession
    {
        return DB::transaction(function () use ($actor, $classroom, $data) {
            $class = $this->lockClass($actor, $classroom);
            $start = CarbonImmutable::parse($data['startsAt'])->utc()->startOfSecond();
            $end = CarbonImmutable::parse($data['endsAt'])->utc()->startOfSecond();
            $this->requireDuration($start, $end);
            $this->requireNoConflict($actor, $start, $end, null, $data['allowConflicts'] ?? false);

            return ClassSession::create(['class_id' => $class->id, 'title' => $data['title'] ?? null, 'room' => $data['room'] ?? null, 'starts_at' => $start, 'ends_at' => $end]);
        });
    }

    public function preview(User $actor, Classroom $classroom, array $data): array
    {
        Gate::forUser($actor)->authorize('update', $classroom);
        $this->requireActive($classroom);
        $occurrences = $this->occurrences($classroom, $data);

        return ['timezone' => $classroom->timezone, 'count' => count($occurrences), 'occurrences' => array_map(fn ($item) => [
            'startsAt' => $item[0]->toISOString(), 'endsAt' => $item[1]->toISOString(),
            'hasConflict' => $this->conflicts($actor, $item[0], $item[1])->exists(),
        ], $occurrences)];
    }

    public function createSeries(User $actor, Classroom $classroom, array $data): array
    {
        return DB::transaction(function () use ($actor, $classroom, $data) {
            $class = $this->lockClass($actor, $classroom);
            $occurrences = $this->occurrences($class, $data);
            foreach ($occurrences as [$start, $end]) {
                $this->requireNoConflict($actor, $start, $end, null, $data['allowConflicts'] ?? false);
            }
            $series = SessionSeries::create(['class_id' => $class->id, 'definition' => [...$data, 'timezone' => $class->timezone]]);
            foreach ($occurrences as [$start, $end]) {
                ClassSession::create(['class_id' => $class->id, 'series_id' => $series->id, 'title' => $data['title'] ?? null, 'room' => $data['room'] ?? null, 'starts_at' => $start, 'ends_at' => $end]);
            }

            return ['seriesId' => $series->id, 'sessionCount' => count($occurrences)];
        });
    }

    public function update(User $actor, Classroom $classroom, string $id, array $data): ClassSession
    {
        return DB::transaction(function () use ($actor, $classroom, $id, $data) {
            $this->lockClass($actor, $classroom);
            $session = ClassSession::where('class_id', $classroom->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            $this->requireVersion($session, $data['expectedVersion']);
            if ($session->status !== 'scheduled' || AttendanceWindow::where('session_id', $session->id)->exists()) {
                throw new ApiException('STATE_CONFLICT', 'A cancelled session or a session with attendance history cannot be rescheduled.');
            }
            $start = CarbonImmutable::parse($data['startsAt'])->utc()->startOfSecond();
            $end = CarbonImmutable::parse($data['endsAt'])->utc()->startOfSecond();
            $this->requireDuration($start, $end);
            $this->requireNoConflict($actor, $start, $end, $id, $data['allowConflicts'] ?? false);
            $session->fill(['starts_at' => $start, 'ends_at' => $end]);
            foreach (['title', 'room'] as $field) {
                if (array_key_exists($field, $data)) {
                    $session->{$field} = $data[$field];
                }
            }
            $session->version++;
            $session->save();

            return $session;
        });
    }

    public function cancel(User $actor, Classroom $classroom, string $id, array $data): ClassSession
    {
        return DB::transaction(function () use ($actor, $classroom, $id, $data) {
            $this->lockClass($actor, $classroom);
            $session = ClassSession::where('class_id', $classroom->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($session->status === 'cancelled') {
                return $session;
            }
            $this->requireVersion($session, $data['expectedVersion']);
            $session->status = 'cancelled';
            $session->cancel_reason = $data['reason'];
            $session->version++;
            $session->save();
            $window = AttendanceWindow::where('session_id', $session->id)->lockForUpdate()->first();
            if ($window) {
                $window->state = 'closed';
                $window->token_version++;
                $window->version++;
                $window->save();
            }

            return $session;
        });
    }

    private function lockClass(User $actor, Classroom $classroom): Classroom
    {
        Gate::forUser($actor)->authorize('update', $classroom);
        DB::table('schedule_owner_locks')->insertOrIgnore(['owner_id' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('schedule_owner_locks')->where('owner_id', $actor->id)->lockForUpdate()->first();
        $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
        $this->requireActive($class);

        return $class;
    }

    private function requireActive(Classroom $class): void
    {
        if ($class->status !== 'active') {
            throw new ApiException('STATE_CONFLICT', 'Archived classes cannot accept schedule changes.');
        }
    }

    private function requireVersion(ClassSession $session, int $version): void
    {
        if ($session->version !== $version) {
            throw new ApiException('VERSION_CONFLICT', 'Session changed. Reload before saving.');
        }
    }

    private function requireDuration(CarbonImmutable $start, CarbonImmutable $end): void
    {
        if ($end->lte($start) || $start->diffInSeconds($end) > 86400) {
            throw new ApiException('INVALID_SCHEDULE', 'Session duration must be positive and at most 24 hours.', 422);
        }
    }

    private function conflicts(User $actor, CarbonImmutable $start, CarbonImmutable $end, ?string $except = null): Builder
    {
        return ClassSession::whereHas('classroom', fn ($q) => $q->where('owner_id', $actor->id))
            ->where('status', 'scheduled')->where('starts_at', '<', $end)->where('ends_at', '>', $start)
            ->when($except, fn ($q) => $q->where('id', '!=', $except));
    }

    private function requireNoConflict(User $actor, CarbonImmutable $start, CarbonImmutable $end, ?string $except, bool $allow): void
    {
        if (! $allow && $this->conflicts($actor, $start, $end, $except)->exists()) {
            throw new ApiException('SCHEDULE_CONFLICT', 'This overlaps another session. Preview and explicitly allow conflicts to continue.');
        }
    }

    private function occurrences(Classroom $class, array $data): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $data['startDate'], 'UTC');
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $data['endDate'], 'UTC');
        if ($start->diffInDays($end) > 366) {
            throw new ApiException('SERIES_TOO_LARGE', 'A recurrence range may not exceed 366 days.', 422);
        }
        $items = [];
        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            if (! in_array($day->dayOfWeekIso, array_map('intval', $data['weekdays']), true)) {
                continue;
            }
            $items[] = [$this->localTime($day->toDateString().' '.$data['startTime'], $class->timezone), $this->localTime($day->toDateString().' '.$data['endTime'], $class->timezone)];
            if (count($items) > 104) {
                throw new ApiException('SERIES_TOO_LARGE', 'Create at most 104 sessions per series.', 422);
            }
        }
        if (! $items) {
            throw new ApiException('EMPTY_SERIES', 'There are no matching weekdays in this range.', 422);
        }

        return $items;
    }

    /** Reject DST gaps/folds instead of silently shifting or choosing an ambiguous local time. */
    private function localTime(string $local, string $zone): CarbonImmutable
    {
        $naive = CarbonImmutable::createFromFormat('!Y-m-d H:i', $local, 'UTC')->getTimestamp();
        $transitions = (new DateTimeZone($zone))->getTransitions($naive - 172800, $naive + 172800);
        $offsets = array_unique(array_column($transitions, 'offset'));
        $matches = [];
        foreach ($offsets as $offset) {
            $candidate = CarbonImmutable::createFromTimestampUTC($naive - $offset);
            if ($candidate->setTimezone($zone)->format('Y-m-d H:i') === $local) {
                $matches[] = $candidate;
            }
        }
        if (count($matches) !== 1) {
            throw new ApiException('AMBIGUOUS_LOCAL_TIME', 'A local time is missing or ambiguous at a daylight-saving transition.', 422);
        }

        return $matches[0];
    }
}
