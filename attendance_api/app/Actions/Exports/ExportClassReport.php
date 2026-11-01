<?php

namespace App\Actions\Exports;

use App\Actions\Attendance\ManageAttendance;
use App\Models\Classroom;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ExportClassReport
{
    public function build(User $actor, Classroom $classroom, string $kind, bool $template = false): array
    {
        Gate::forUser($actor)->authorize('update', $classroom);
        if ($kind === 'attendance' && ! $template) {
            app(ManageAttendance::class)->finalizeAllDue($classroom->id);
        }

        return DB::transaction(function () use ($actor, $classroom, $kind, $template) {
            $class = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $class);
            $members = $template ? collect() : DB::table('class_memberships as m')->join('users as u', 'u.id', '=', 'm.student_id')->where('m.class_id', $class->id)->whereIn('m.status', ['active', 'left', 'removed'])->select('u.*', 'm.status as membership_status')->orderBy('u.display_name')->orderBy('u.id')->limit(5001)->get();
            abort_if($members->count() > 5000, 422, 'Report exceeds 5000 students.');
            $base = ['STT', 'Mã sinh viên', 'Họ và tên', 'Giới tính', 'Ngày sinh', 'Trạng thái thành viên'];
            $metadata = [['Thông tin', 'Giá trị'], ['Loại báo cáo', $kind === 'attendance' ? 'Điểm danh' : 'Điểm đã công bố'], ['Lớp', $class->name], ['Giáo viên', $actor->display_name], ['Múi giờ lớp', $class->timezone], ['Thời điểm xuất', now()->setTimezone($class->timezone)->format('d/m/Y H:i:s')], ['Phạm vi thành viên', 'Đang học, đã rời, bị loại; không gồm chờ duyệt/từ chối'], ['Mã sinh viên', 'Để trống khi chưa có; không dùng ID hệ thống thay mã sinh viên']];
            if ($kind === 'attendance') {
                $columns = $template ? collect() : DB::table('class_sessions')->where('class_id', $class->id)->where('status', '!=', 'cancelled')->orderBy('starts_at')->orderBy('id')->limit(501)->get();
                abort_if($columns->count() > 500 || $members->count() * ($columns->count() + 11) > 100000, 422, 'Report exceeds export limits.');
                $values = $template ? collect() : DB::table('attendance_records as r')->join('attendance_windows as w', 'w.id', '=', 'r.window_id')->whereIn('r.session_id', $columns->pluck('id'))->select('r.*', 'w.state as window_state', 'w.closes_at')->get()->keyBy(fn ($r) => $r->student_id.':'.$r->session_id);
                $headers = $columns->map(fn ($s) => CarbonImmutable::parse($s->starts_at, 'UTC')->setTimezone($class->timezone)->format('d/m/Y H:i').' · '.($s->title ?: 'Buổi học'))->all();
                $headers = [...$base, ...($template ? ['Buổi học (ngày/giờ)'] : $headers), 'Có mặt', 'Đi muộn', 'Vắng', 'Buổi đã chốt', 'Tỷ lệ tham dự (%)'];
                $roster = $template ? collect() : DB::table('attendance_rosters as r')->join('attendance_windows as w', 'w.id', '=', 'r.window_id')->whereIn('w.session_id', $columns->pluck('id'))->select('r.student_id', 'w.session_id')->get()->keyBy(fn ($r) => $r->student_id.':'.$r->session_id);
                $metadata[] = ['Ký hiệu', 'P: có mặt; L: muộn; A: vắng; ?: chưa chốt; —: không thuộc roster/chưa mở. Chỉ buổi đã chốt tính tổng.'];
            } else {
                $columns = $template ? collect() : DB::table('assignments')->where('class_id', $class->id)->where('state', '!=', 'draft')->orderBy('created_at')->orderBy('id')->limit(501)->get();
                abort_if($columns->count() > 500 || $members->count() * ($columns->count() + 11) > 100000, 422, 'Report exceeds export limits.');
                $values = $template ? collect() : DB::table('submissions as s')->join('grade_publications as p', 'p.id', '=', 's.published_grade_publication_id')->whereIn('s.assignment_id', $columns->pluck('id'))->select('s.student_id', 's.assignment_id', 'p.score')->get()->keyBy(fn ($r) => $r->student_id.':'.$r->assignment_id);
                $headers = [...$base, ...($template ? ['Tên bài tập /10'] : $columns->map(fn ($a) => $a->title.' /10')->all()), 'Số bài có điểm', 'Trung bình /10'];
                $metadata[] = ['Quy tắc điểm', 'Chỉ điểm đã công bố hiện hành; ô trống không tính 0. Trung bình trọng số bằng nhau; chưa gồm Quiz.'];
            }
            abort_if($columns->count() > 500, 422, 'Report exceeds 500 columns.');
            abort_if($members->count() * max(1, count($headers)) > 100000, 422, 'Report exceeds 100000 cells.');
            $metadata[] = ['Môn học', $class->course_id ? (DB::table('courses')->where('id', $class->course_id)->value('name') ?? '') : ''];
            $metadata[] = ['Học kỳ', $class->semester_id ? (DB::table('semesters')->where('id', $class->semester_id)->value('name') ?? '') : ''];
            $rows = [$headers];
            foreach ($members as $i => $member) {
                $row = [$i + 1, $member->student_code ?? '', $member->display_name, ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác', 'undisclosed' => 'Không chia sẻ'][$member->gender ?? 'undisclosed'] ?? '', $member->birthday ? CarbonImmutable::parse($member->birthday)->format('d/m/Y') : '', ['active' => 'Đang học', 'left' => 'Đã rời', 'removed' => 'Bị loại'][$member->membership_status]];
                $present = 0;
                $late = 0;
                $absent = 0;
                $scores = [];
                foreach ($columns as $column) {
                    $value = $values->get($member->id.':'.$column->id);
                    if ($kind === 'attendance') {
                        $final = $value && ($value->window_state !== 'open' || CarbonImmutable::parse($value->closes_at, 'UTC')->lte(now()));
                        $row[] = ! $value ? ($roster->has($member->id.':'.$column->id) ? '?' : '—') : (! $final ? '?' : (['present' => 'P', 'late' => 'L', 'absent' => 'A'][$value->status] ?? '?'));
                        if ($final) {
                            $present += $value->status === 'present' ? 1 : 0;
                            $late += $value->status === 'late' ? 1 : 0;
                            $absent += $value->status === 'absent' ? 1 : 0;
                        }
                    } else {
                        $row[] = $value ? (float) $value->score : '';
                        if ($value) {
                            $scores[] = (float) $value->score;
                        }
                    }
                }
                $total = $present + $late + $absent;
                $rows[] = [...$row, ...($kind === 'attendance' ? [$present, $late, $absent, $total, $total ? round(100 * ($present + $late) / $total, 2) : ''] : [count($scores), count($scores) ? round(array_sum($scores) / count($scores), 2) : ''])];
            }

            return [['name' => $kind === 'attendance' ? 'Diem danh' : 'Bang diem', 'rows' => $rows], ['name' => 'Thong tin', 'rows' => $metadata]];
        });
    }
}
