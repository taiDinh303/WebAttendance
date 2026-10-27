<?php

namespace Tests\Unit;

use App\Actions\Schedule\WeeklyOccurrences;
use App\Exceptions\ApiException;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WeeklyOccurrencesTest extends TestCase
{
    private static function definition(array $overrides = []): array
    {
        return [...[
            'startDate' => '2026-10-26', 'endDate' => '2026-11-02',
            'weekdays' => [1], 'startTime' => '09:00', 'endTime' => '10:00',
        ], ...$overrides];
    }

    public function test_includes_both_boundary_dates_and_converts_local_time_to_utc(): void
    {
        $occurrences = (new WeeklyOccurrences)->generate('Asia/Ho_Chi_Minh', self::definition());

        $this->assertSame([
            ['2026-10-26T02:00:00Z', '2026-10-26T03:00:00Z'],
            ['2026-11-02T02:00:00Z', '2026-11-02T03:00:00Z'],
        ], $this->instants($occurrences));
    }

    public function test_preserves_local_wall_time_across_daylight_saving_change(): void
    {
        $occurrences = (new WeeklyOccurrences)->generate('America/New_York', self::definition([
            'startDate' => '2026-10-25', 'endDate' => '2026-11-01', 'weekdays' => [7],
        ]));

        $this->assertSame([
            ['2026-10-25T13:00:00Z', '2026-10-25T14:00:00Z'],
            ['2026-11-01T14:00:00Z', '2026-11-01T15:00:00Z'],
        ], $this->instants($occurrences));
    }

    public function test_supports_single_day_string_weekday_and_previous_utc_date(): void
    {
        $occurrences = (new WeeklyOccurrences)->generate('Asia/Ho_Chi_Minh', self::definition([
            'endDate' => '2026-10-26', 'weekdays' => ['1'], 'startTime' => '00:30', 'endTime' => '01:30',
        ]));

        $this->assertSame([['2026-10-25T17:30:00Z', '2026-10-25T18:30:00Z']], $this->instants($occurrences));
    }

    public function test_accepts_exactly_104_sessions(): void
    {
        $occurrences = (new WeeklyOccurrences)->generate('UTC', self::definition([
            'startDate' => '2026-01-01', 'endDate' => '2026-04-14', 'weekdays' => [1, 2, 3, 4, 5, 6, 7],
        ]));

        $this->assertCount(104, $occurrences);
        $this->assertSame('2026-04-14T09:00:00Z', $occurrences[103][0]->format('Y-m-d\TH:i:s\Z'));
    }

    public function test_accepts_exactly_366_days_when_session_limit_is_respected(): void
    {
        $occurrences = (new WeeklyOccurrences)->generate('UTC', self::definition([
            'startDate' => '2026-01-01', 'endDate' => '2027-01-02',
        ]));

        $this->assertCount(52, $occurrences);
    }

    #[DataProvider('rejectedDefinitions')]
    public function test_rejects_invalid_recurrence_with_domain_error(string $timezone, array $overrides, string $error): void
    {
        try {
            (new WeeklyOccurrences)->generate($timezone, self::definition($overrides));
        } catch (ApiException $exception) {
            $this->assertSame($error, $exception->errorCode);
            $this->assertSame(422, $exception->status);

            return;
        }
        $this->fail('Expected recurrence to be rejected.');
    }

    public static function rejectedDefinitions(): array
    {
        return [
            '367 day range' => ['UTC', ['startDate' => '2026-01-01', 'endDate' => '2027-01-03'], 'SERIES_TOO_LARGE'],
            '105 sessions' => ['UTC', ['startDate' => '2026-01-01', 'endDate' => '2026-04-15', 'weekdays' => [1, 2, 3, 4, 5, 6, 7]], 'SERIES_TOO_LARGE'],
            'no matching weekday' => ['UTC', ['endDate' => '2026-10-26', 'weekdays' => [2]], 'EMPTY_SERIES'],
            'missing DST start' => ['America/New_York', ['startDate' => '2027-03-14', 'endDate' => '2027-03-14', 'weekdays' => [7], 'startTime' => '02:30', 'endTime' => '04:00'], 'AMBIGUOUS_LOCAL_TIME'],
            'ambiguous DST end' => ['America/New_York', ['startDate' => '2026-11-01', 'endDate' => '2026-11-01', 'weekdays' => [7], 'startTime' => '00:30', 'endTime' => '01:30'], 'AMBIGUOUS_LOCAL_TIME'],
        ];
    }

    private function instants(array $occurrences): array
    {
        return array_map(fn (array $pair): array => array_map(fn (CarbonImmutable $instant): string => $instant->format('Y-m-d\TH:i:s\Z'), $pair), $occurrences);
    }
}
