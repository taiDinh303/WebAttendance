<?php

namespace Tests\Feature;

use App\Models\AttendanceRoster;
use App\Models\AttendanceWindow;
use App\Models\ClassMembership;
use App\Models\ClassSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScheduleAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(bool $withSession = true, string $timezone = 'Asia/Ho_Chi_Minh'): array
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-27T02:00:00Z'));
        $teacher = User::factory()->create(['role' => 'teacher']);
        $students = User::factory()->count(2)->create();
        $classId = $this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => 'Calendar class', 'timezone' => $timezone])->assertCreated()->json('data.class.id');
        foreach ($students as $student) {
            ClassMembership::create(['class_id' => $classId, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => now(), 'joined_at' => now()]);
        }
        $sessionId = $withSession ? $this->postJson('/api/v1/classes/'.$classId.'/sessions', [
            'startsAt' => now()->toISOString(), 'endsAt' => now()->addHour()->toISOString(), 'title' => 'Lesson',
        ])->assertCreated()->json('data.id') : null;

        return [$teacher, $students[0], $students[1], $classId, $sessionId];
    }

    private function open(string $classId, string $sessionId): string
    {
        return $this->postJson('/api/v1/classes/'.$classId.'/sessions/'.$sessionId.'/attendance-windows', [
            'opensAt' => now()->toISOString(), 'lateAfter' => now()->addMinutes(5)->toISOString(), 'closesAt' => now()->addMinutes(10)->toISOString(),
        ])->assertCreated()->json('data.id');
    }

    private function qr(User $teacher, string $windowId): string
    {
        return $this->actingAs($teacher)->getJson('/api/v1/attendance-windows/'.$windowId.'/qr')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data.qrToken');
    }

    public function test_weekly_series_uses_class_timezone_and_edit_cancel_affects_only_one_session(): void
    {
        [$teacher, $student, , $classId] = $this->workspace(false);
        $series = ['startDate' => '2026-10-27', 'endDate' => '2026-11-10', 'weekdays' => [2], 'startTime' => '09:00', 'endTime' => '10:00'];
        $this->postJson('/api/v1/classes/'.$classId.'/session-series/preview', $series)->assertOk()->assertJsonPath('data.count', 3)->assertJsonPath('data.occurrences.0.startsAt', '2026-10-27T02:00:00.000000Z');
        $this->postJson('/api/v1/classes/'.$classId.'/session-series', $series)->assertCreated()->assertJsonPath('data.sessionCount', 3);
        $query = '?from=2026-10-26T00:00:00Z&to=2026-11-11T00:00:00Z&pageSize=2';
        $first = $this->getJson('/api/v1/calendar'.$query)->assertOk()->assertJsonPath('data.totalItems', 3)->assertJsonCount(2, 'data.items')->json('data.items');
        $this->patchJson('/api/v1/classes/'.$classId.'/sessions/'.$first[0]['id'], ['startsAt' => '2026-10-27T02:30:00Z', 'endsAt' => '2026-10-27T03:30:00Z', 'expectedVersion' => 1])->assertOk()->assertJsonPath('data.version', 2);
        $this->postJson('/api/v1/classes/'.$classId.'/sessions/'.$first[1]['id'].'/cancel', ['expectedVersion' => 1, 'reason' => 'Teacher unavailable'])->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->actingAs($student)->getJson('/api/v1/calendar'.$query)->assertOk()->assertJsonPath('data.totalItems', 3);
        $this->assertDatabaseCount('class_sessions', 3);
        $this->assertDatabaseHas('class_sessions', ['id' => $first[0]['id'], 'status' => 'scheduled', 'version' => 2]);
    }

    public function test_schedule_overlap_requires_explicit_acknowledgment_and_versions_are_enforced(): void
    {
        [, , , $classId, $sessionId] = $this->workspace();
        $body = ['startsAt' => '2026-10-27T02:30:00Z', 'endsAt' => '2026-10-27T03:30:00Z'];
        $this->postJson('/api/v1/classes/'.$classId.'/sessions', $body)->assertConflict()->assertJsonPath('code', 'SCHEDULE_CONFLICT');
        $this->postJson('/api/v1/classes/'.$classId.'/sessions', [...$body, 'allowConflicts' => true])->assertCreated();
        $this->patchJson('/api/v1/classes/'.$classId.'/sessions/'.$sessionId, [...$body, 'expectedVersion' => 99])->assertConflict();
        $this->postJson('/api/v1/classes/'.$classId.'/sessions', ['startsAt' => '2026-10-27 04:00:00', 'endsAt' => '2026-10-27 05:00:00'])->assertUnprocessable();
        $this->postJson('/api/v1/classes/'.$classId.'/sessions', ['startsAt' => '2026-10-28T04:00:00Z', 'endsAt' => '2026-10-30T05:00:00Z'])->assertUnprocessable();
        $this->getJson('/api/v1/calendar?from=2026-01-01T00:00:00Z&to=2026-12-01T00:00:00Z')->assertUnprocessable();
    }

    public function test_recurrence_keeps_local_hour_across_dst_and_rejects_ambiguous_and_missing_times(): void
    {
        [, , , $classId] = $this->workspace(false, 'America/New_York');
        $series = ['startDate' => '2026-10-25', 'endDate' => '2026-11-01', 'weekdays' => [7], 'startTime' => '09:00', 'endTime' => '10:00'];
        $this->postJson('/api/v1/classes/'.$classId.'/session-series/preview', $series)->assertOk()
            ->assertJsonPath('data.occurrences.0.startsAt', '2026-10-25T13:00:00.000000Z')->assertJsonPath('data.occurrences.1.startsAt', '2026-11-01T14:00:00.000000Z');
        $this->postJson('/api/v1/classes/'.$classId.'/session-series/preview', [...$series, 'startDate' => '2026-11-01', 'startTime' => '01:30', 'endTime' => '03:00'])->assertUnprocessable()->assertJsonPath('code', 'AMBIGUOUS_LOCAL_TIME');
        $this->postJson('/api/v1/classes/'.$classId.'/session-series/preview', [...$series, 'startDate' => '2027-03-14', 'endDate' => '2027-03-14', 'startTime' => '02:30', 'endTime' => '04:00'])->assertUnprocessable();
    }

    public function test_schedule_scope_and_cross_parent_ids_cannot_be_bypassed(): void
    {
        [$teacher, $student, , $classId, $sessionId] = $this->workspace();
        $other = User::factory()->create(['role' => 'teacher']);
        $range = '?from=2026-10-27T00:00:00Z&to=2026-10-28T00:00:00Z';
        $this->actingAs($other)->getJson('/api/v1/calendar'.$range)->assertOk()->assertJsonPath('data.totalItems', 0);
        $this->getJson('/api/v1/classes/'.$classId.'/sessions/'.$sessionId)->assertNotFound();
        $this->postJson('/api/v1/classes/'.$classId.'/sessions', ['startsAt' => '2026-10-28T02:00:00Z', 'endsAt' => '2026-10-28T03:00:00Z'])->assertNotFound();
        $otherClass = $this->postJson('/api/v1/classes', ['name' => 'Other'])->json('data.class.id');
        $this->getJson('/api/v1/classes/'.$otherClass.'/sessions/'.$sessionId)->assertNotFound();
        $this->actingAs($student)->postJson('/api/v1/classes/'.$classId.'/sessions', ['startsAt' => '2026-10-28T02:00:00Z', 'endsAt' => '2026-10-28T03:00:00Z'])->assertNotFound();
        $this->postJson('/api/v1/classes/'.$classId.'/leave', ['expectedVersion' => 1])->assertOk();
        $this->getJson('/api/v1/calendar'.$range)->assertOk()->assertJsonPath('data.totalItems', 0);
    }

    public function test_qr_check_in_is_self_only_and_duplicate_does_not_change_status_or_timestamp(): void
    {
        [$teacher, $student, , $classId, $sessionId] = $this->workspace();
        $this->travelTo(CarbonImmutable::parse('2026-10-27T02:00:00.123456Z'));
        $window = $this->open($classId, $sessionId);
        $token = $this->qr($teacher, $window);
        $first = $this->actingAs($student)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertCreated()->assertJsonPath('data.status', 'present');
        $this->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertOk()->assertJsonPath('data.id', $first->json('data.id'))->assertJsonPath('data.checkedInAt', $first->json('data.checkedInAt'));
        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseCount('attendance_audits', 1);
        $this->getJson('/api/v1/me/attendance?pageSize=1')->assertOk()->assertJsonPath('data.totalItems', 1);
        $this->getJson('/api/v1/attendance-windows/'.$window.'/qr')->assertNotFound();
        $this->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token, 'studentId' => $teacher->id])->assertUnprocessable();
        $this->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token.'forged'])->assertUnprocessable();
    }

    public function test_qr_rotates_every_30_seconds_overlaps_for_60_seconds_and_then_expires(): void
    {
        [$teacher, $student, $second, $classId, $sessionId] = $this->workspace();
        $window = $this->open($classId, $sessionId);
        $old = $this->qr($teacher, $window);
        $this->travel(30)->seconds();
        $new = $this->qr($teacher, $window);
        $this->assertNotSame($old, $new);
        $this->actingAs($student)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $old])->assertCreated();
        $this->travel(30)->seconds();
        $this->actingAs($second)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $old])->assertStatus(410);
        $this->postJson('/api/v1/attendance/check-ins', ['qrToken' => $new])->assertCreated();
    }

    public function test_late_threshold_and_close_deadline_use_server_time(): void
    {
        [$teacher, $student, $second, $classId, $sessionId] = $this->workspace();
        $window = $this->open($classId, $sessionId);
        $this->travel(5)->minutes();
        $token = $this->qr($teacher, $window);
        $this->actingAs($student)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertCreated()->assertJsonPath('data.status', 'late');
        $this->travel(5)->minutes();
        $this->actingAs($second)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertStatus(410);
        $this->actingAs($teacher)->getJson('/api/v1/classes/'.$classId.'/attendance')->assertOk()->assertJsonPath('data.totalItems', 2);
        $this->assertDatabaseHas('attendance_records', ['session_id' => $sessionId, 'student_id' => $second->id, 'status' => 'absent']);
    }

    public function test_roster_is_fixed_at_open_and_removal_revokes_check_in_even_if_snapshotted(): void
    {
        [$teacher, $student, , $classId, $sessionId] = $this->workspace();
        $window = $this->open($classId, $sessionId);
        $token = $this->qr($teacher, $window);
        $newStudent = User::factory()->create();
        ClassMembership::create(['class_id' => $classId, 'student_id' => $newStudent->id, 'status' => 'active', 'requested_at' => now()]);
        $this->actingAs($newStudent)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertForbidden()->assertJsonPath('code', 'NOT_IN_ATTENDANCE_ROSTER');
        $this->actingAs($teacher)->getJson('/api/v1/attendance-windows/'.$window.'/roster?pageSize=1')->assertOk()->assertJsonPath('data.totalItems', 2)->assertJsonCount(1, 'data.items');
        $this->postJson('/api/v1/classes/'.$classId.'/members/'.$student->id.'/remove', ['expectedVersion' => 1])->assertOk();
        $this->actingAs($student)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertNotFound();
    }

    public function test_close_finalizes_absence_once_and_manual_correction_has_reason_version_and_audit(): void
    {
        [$teacher, $student, , $classId, $sessionId] = $this->workspace();
        $window = $this->open($classId, $sessionId);
        $token = $this->qr($teacher, $window);
        $this->postJson('/api/v1/attendance-windows/'.$window.'/close', ['expectedVersion' => 1])->assertOk();
        $this->postJson('/api/v1/attendance-windows/'.$window.'/close', ['expectedVersion' => 1])->assertOk();
        $this->assertDatabaseCount('attendance_records', 2);
        $this->assertDatabaseCount('attendance_audits', 2);
        $url = '/api/v1/classes/'.$classId.'/sessions/'.$sessionId.'/attendance/'.$student->id;
        $this->putJson($url, ['status' => 'present', 'expectedVersion' => 1])->assertUnprocessable();
        $record = $this->putJson($url, ['status' => 'present', 'expectedVersion' => 1, 'reason' => 'Student device failed'])->assertOk()->assertJsonPath('data.version', 2)->assertJsonPath('data.checkedInAt', null)->json('data.id');
        $this->putJson($url, ['status' => 'late', 'expectedVersion' => 1, 'reason' => 'Stale update'])->assertConflict();
        $this->getJson('/api/v1/classes/'.$classId.'/attendance/'.$record.'/audit')->assertOk()->assertJsonPath('data.totalItems', 2);
        $this->assertDatabaseHas('attendance_audits', ['record_id' => $record, 'actor_id' => $teacher->id, 'action' => 'manual_correction', 'reason' => 'Student device failed']);
        $summary = $this->getJson('/api/v1/classes/'.$classId.'/attendance-summary')->assertOk()->assertJsonPath('data.totalItems', 2);
        $studentTotal = collect($summary->json('data.items'))->firstWhere('studentId', $student->id);
        $this->assertSame(1, $studentTotal['present']);
        $this->assertSame(0, $studentTotal['absent']);
        $this->assertSame(1, $studentTotal['total']);
        $this->assertSame(1, $studentTotal['attendanceRate']);
        $this->actingAs($student)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertConflict();
        $this->putJson($url, ['status' => 'present', 'expectedVersion' => 2, 'reason' => 'Not allowed'])->assertNotFound();
    }

    public function test_manual_mark_before_close_is_not_overwritten_by_qr_or_finalization(): void
    {
        [$teacher, $student, , $classId, $sessionId] = $this->workspace();
        $window = $this->open($classId, $sessionId);
        $this->putJson('/api/v1/classes/'.$classId.'/sessions/'.$sessionId.'/attendance/'.$student->id, [
            'status' => 'present', 'expectedVersion' => 0, 'reason' => 'Teacher confirmed presence',
        ])->assertOk()->assertJsonPath('data.recordedSource', 'manual');
        $token = $this->qr($teacher, $window);
        $this->actingAs($student)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertOk()->assertJsonPath('data.recordedSource', 'manual');
        $this->actingAs($teacher)->postJson('/api/v1/attendance-windows/'.$window.'/close', ['expectedVersion' => 1])->assertOk();
        $this->assertDatabaseHas('attendance_records', ['student_id' => $student->id, 'status' => 'present', 'recorded_source' => 'manual', 'version' => 1]);
    }

    public function test_cancel_closes_qr_preserves_history_and_excludes_session_from_summary(): void
    {
        [$teacher, $student, $second, $classId, $sessionId] = $this->workspace();
        $window = $this->open($classId, $sessionId);
        $token = $this->qr($teacher, $window);
        $this->actingAs($student)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertCreated();
        $this->actingAs($teacher)->patchJson('/api/v1/classes/'.$classId.'/sessions/'.$sessionId, ['startsAt' => '2026-10-27T04:00:00Z', 'endsAt' => '2026-10-27T05:00:00Z', 'expectedVersion' => 1])->assertConflict();
        $this->postJson('/api/v1/classes/'.$classId.'/archive', ['expectedVersion' => 1])->assertConflict();
        $this->postJson('/api/v1/classes/'.$classId.'/sessions/'.$sessionId.'/cancel', ['expectedVersion' => 1, 'reason' => 'School closed'])->assertOk();
        $this->getJson('/api/v1/classes/'.$classId.'/attendance-summary')->assertOk()->assertJsonPath('data.totalItems', 0);
        $this->actingAs($second)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertConflict();
        $this->assertDatabaseCount('attendance_records', 1);
        $this->actingAs($student)->getJson('/api/v1/me/attendance')->assertOk()->assertJsonPath('data.items.0.sessionStatus', 'cancelled');
    }

    public function test_expiry_command_is_idempotent_and_preserves_manual_updates(): void
    {
        [, , , $classId, $sessionId] = $this->workspace();
        $this->open($classId, $sessionId);
        $this->travel(11)->minutes();
        $this->artisan('attendance:finalize')->expectsOutput('Finalized 1 attendance windows.')->assertSuccessful();
        $this->artisan('attendance:finalize')->expectsOutput('Finalized 0 attendance windows.')->assertSuccessful();
        $this->assertDatabaseCount('attendance_records', 2);
        $this->assertDatabaseCount('attendance_audits', 2);
    }

    public function test_more_than_30_students_on_one_network_can_check_in_without_sharing_actor_rate_limits(): void
    {
        [$teacher, , , $classId, $sessionId] = $this->workspace();
        $students = User::factory()->count(31)->create();
        foreach ($students as $person) {
            ClassMembership::create(['class_id' => $classId, 'student_id' => $person->id, 'status' => 'active', 'requested_at' => now(), 'joined_at' => now()]);
        }
        $window = $this->open($classId, $sessionId);
        $token = $this->qr($teacher, $window);

        foreach ($students as $person) {
            $this->actingAs($person)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->assertCreated();
        }
        $this->assertDatabaseCount('attendance_records', 31);
    }

    public function test_summary_drains_more_than_one_expiry_batch_before_returning_totals(): void
    {
        [, $student, $second, $classId] = $this->workspace(false);
        for ($i = 0; $i < 101; $i++) {
            $session = ClassSession::create(['class_id' => $classId, 'starts_at' => now()->subDays($i + 1), 'ends_at' => now()->subDays($i + 1)->addHour()]);
            $window = AttendanceWindow::create(['session_id' => $session->id, 'opened_by' => auth()->id(), 'opens_at' => now()->subHours(2), 'closes_at' => now()->subHour()]);
            foreach ([$student, $second] as $person) {
                AttendanceRoster::create(['window_id' => $window->id, 'student_id' => $person->id]);
            }
        }

        $summary = $this->getJson('/api/v1/classes/'.$classId.'/attendance-summary')->assertOk()->assertJsonPath('data.totalItems', 2);
        foreach ($summary->json('data.items') as $row) {
            $this->assertSame(101, $row['absent']);
            $this->assertSame(101, $row['total']);
        }
        $this->assertDatabaseCount('attendance_records', 202);
        $this->assertDatabaseCount('attendance_audits', 202);
    }

    public function test_teacher_cannot_correct_or_audit_another_teachers_attendance_and_student_counts_are_private(): void
    {
        [$teacher, $student, $second, $classId, $sessionId] = $this->workspace();
        $window = $this->open($classId, $sessionId);
        $token = $this->qr($teacher, $window);
        $record = $this->actingAs($student)->postJson('/api/v1/attendance/check-ins', ['qrToken' => $token])->json('data.id');
        $this->getJson('/api/v1/me/attendance?studentId='.$second->id)->assertForbidden();
        $other = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($other)->getJson('/api/v1/classes/'.$classId.'/attendance')->assertNotFound();
        $this->getJson('/api/v1/classes/'.$classId.'/attendance/'.$record.'/audit')->assertNotFound();
        $this->putJson('/api/v1/classes/'.$classId.'/sessions/'.$sessionId.'/attendance/'.$student->id, ['status' => 'absent', 'reason' => 'Unauthorized', 'expectedVersion' => 1])->assertNotFound();
        $this->actingAs($second)->getJson('/api/v1/me/attendance')->assertOk()->assertJsonPath('data.totalItems', 0);
    }
}
