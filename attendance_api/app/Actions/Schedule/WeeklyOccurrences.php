<?php

namespace App\Actions\Schedule;

use App\Exceptions\ApiException;
use Carbon\CarbonImmutable;
use DateTimeZone;

final class WeeklyOccurrences
{
    private const MAX_RANGE_DAYS = 366;

    private const MAX_SESSIONS = 104;

    /**
     * Expand a validated weekly definition into UTC instants without persistence.
     *
     * @param  array{startDate: string, endDate: string, weekdays: list<int|string>, startTime: string, endTime: string}  $definition
     * @return list<array{CarbonImmutable, CarbonImmutable}>
     */
    public function generate(string $timezone, array $definition): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $definition['startDate'], 'UTC');
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $definition['endDate'], 'UTC');
        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            throw new ApiException('SERIES_TOO_LARGE', 'A recurrence range may not exceed 366 days.', 422);
        }
        $weekdays = array_map('intval', $definition['weekdays']);
        $occurrences = [];
        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            if (! in_array($day->dayOfWeekIso, $weekdays, true)) {
                continue;
            }
            $date = $day->toDateString();
            $occurrences[] = [
                $this->localTime($date.' '.$definition['startTime'], $timezone),
                $this->localTime($date.' '.$definition['endTime'], $timezone),
            ];
            if (count($occurrences) > self::MAX_SESSIONS) {
                throw new ApiException('SERIES_TOO_LARGE', 'Create at most 104 sessions per series.', 422);
            }
        }
        if (! $occurrences) {
            throw new ApiException('EMPTY_SERIES', 'There are no matching weekdays in this range.', 422);
        }

        return $occurrences;
    }

    /** Reject DST gaps/folds instead of silently shifting or choosing an ambiguous local time. */
    private function localTime(string $local, string $timezone): CarbonImmutable
    {
        $naive = CarbonImmutable::createFromFormat('!Y-m-d H:i', $local, 'UTC')->getTimestamp();
        $transitions = (new DateTimeZone($timezone))->getTransitions($naive - 172800, $naive + 172800);
        $offsets = array_unique(array_column($transitions, 'offset'));
        $matches = [];
        foreach ($offsets as $offset) {
            $candidate = CarbonImmutable::createFromTimestampUTC($naive - $offset);
            if ($candidate->setTimezone($timezone)->format('Y-m-d H:i') === $local) {
                $matches[] = $candidate;
            }
        }
        if (count($matches) !== 1) {
            throw new ApiException('AMBIGUOUS_LOCAL_TIME', 'A local time is missing or ambiguous at a daylight-saving transition.', 422);
        }

        return $matches[0];
    }
}
