<?php

namespace App\Actions\Accounts;

use App\Actions\Announcements\ManageAnnouncements;
use App\Actions\Assignments\ManageAssignments;
use App\Actions\Assignments\ManageGrades;
use App\Actions\Assignments\ManageSubmissions;
use App\Actions\Classes\ManageClassroom;
use App\Actions\Classes\SaveCourse;
use App\Actions\Classes\SaveSemester;
use App\Models\AttendanceAudit;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRoster;
use App\Models\AttendanceWindow;
use App\Models\ClassMembership;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class PortfolioDemo
{
    public const DATASET = 'recruiter-portfolio-v1';

    public const PASSWORD = 'AttendanceDemo!2026';

    public function manifest(): array
    {
        $value = DB::table('demo_datasets')->where('name', self::DATASET)->value('manifest');

        return $value ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : [];
    }

    public function contains(User $user): bool
    {
        return in_array($user->id, $this->manifest()['userIds'] ?? [], true);
    }

    public function handle(): array
    {
        $mailer = config('mail.default');
        config(['mail.default' => 'array']);
        try {
            return $this->maintain();
        } finally {
            config(['mail.default' => $mailer]);
        }
    }

    private function maintain(): array
    {
        $this->guard();

        return DB::transaction(function () {
            DB::table('demo_datasets')->insertOrIgnore(['name' => self::DATASET, 'manifest' => '{}', 'created_at' => now(), 'updated_at' => now()]);
            $row = DB::table('demo_datasets')->where('name', self::DATASET)->lockForUpdate()->first();
            $manifest = json_decode($row->manifest, true, 512, JSON_THROW_ON_ERROR);
            if ($manifest && CarbonImmutable::parse($manifest['resetAt'])->addDay()->isFuture() && ! $this->overLimit($manifest)) {
                return $manifest;
            }
            if ($manifest) {
                $this->clearWorkspace($manifest);
            }
            $teacher = $this->account('teacher@demo.attendance.test', 'Nguyễn Minh Anh · GV demo', 'teacher', $manifest);
            $students = [];
            $names = ['Trần Minh Khang', 'Nguyễn Thảo Vy', 'Lê Hoàng Phúc', 'Phạm Ngọc Hà', 'Võ Gia Huy', 'Đặng Mai Chi', 'Bùi Đức Anh', 'Đỗ Quỳnh Trang', 'Hoàng Nhật Nam', 'Phan Hải Yến', 'Vũ Thanh Tâm', 'Dương Tuấn Kiệt'];
            foreach ($names as $index => $name) {
                $email = $index === 0 ? 'student@demo.attendance.test' : 'student'.($index + 1).'@demo.attendance.test';
                $student = $this->account($email, $name, 'student', $manifest);
                $student->forceFill(['student_code' => 'SV'.sprintf('%04d', 2401 + $index), 'gender' => $index % 2 ? 'female' : 'male', 'birthday' => '2005-'.sprintf('%02d', $index % 12 + 1).'-12'])->save();
                $students[] = $student;
            }
            $now = CarbonImmutable::now('UTC')->startOfSecond();
            $semester = app(SaveSemester::class)->handle($teacher, ['name' => 'Học kỳ 1 · 2026–2027', 'startDate' => $now->subMonths(2)->toDateString(), 'endDate' => $now->addMonths(3)->toDateString(), 'isActive' => true]);
            $classIds = [];
            foreach (['Lập trình Web · WEB01', 'Cơ sở dữ liệu · CSDL02', 'Thiết kế giao diện · UI03'] as $index => $name) {
                $course = app(SaveCourse::class)->handle($teacher, ['name' => $name, 'code' => 'DEMO-'.($index + 1)]);
                [$class] = app(ManageClassroom::class)->create($teacher, ['name' => $name, 'description' => 'Không gian trải nghiệm tuyển dụng. Toàn bộ hồ sơ, bài nộp và kết quả là dữ liệu giả lập.', 'timezone' => 'Asia/Ho_Chi_Minh', 'courseId' => $course->id, 'semesterId' => $semester->id]);
                $classIds[] = $class->id;
                foreach ($students as $position => $student) {
                    ClassMembership::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => $position > 9 ? 'pending' : 'active', 'requested_at' => $now, 'joined_at' => $position > 9 ? null : $now->subMonths(1), 'reviewed_at' => $position > 9 ? null : $now, 'reviewed_by' => $position > 9 ? null : $teacher->id]);
                }
                $this->populate($teacher, $class, array_slice($students, 0, 10), $now);
            }
            $manifest = ['teacherId' => $teacher->id, 'userIds' => [$teacher->id, ...array_map(fn (User $u) => $u->id, $students)], 'classIds' => $classIds, 'resetAt' => now()->toISOString()];
            DB::table('demo_datasets')->where('name', self::DATASET)->update(['manifest' => json_encode($manifest, JSON_THROW_ON_ERROR), 'updated_at' => now()]);

            return $manifest;
        });
    }

    private function account(string $email, string $name, string $role, array $manifest): User
    {
        $user = User::where('email', $email)->first();
        if ($user && ! in_array($user->id, $manifest['userIds'] ?? [], true)) {
            throw new RuntimeException('Demo email collision: refusing to take over an existing account.');
        }
        $user ??= new User;
        $user->forceFill(['email' => $email, 'display_name' => $name, 'role' => $role, 'password' => self::PASSWORD, 'account_status' => 'active', 'email_verified_at' => now()])->save();

        return $user;
    }

    public function overLimit(array $manifest): bool
    {
        $classes = DB::table('classes')->where('owner_id', $manifest['teacherId'])->pluck('id');

        return DB::table('attendance_audits')->whereIn('record_id', DB::table('attendance_records')->whereIn('session_id', DB::table('class_sessions')->whereIn('class_id', $classes)->select('id'))->select('id'))->count() > 1000
            || DB::table('membership_events')->whereIn('membership_id', DB::table('class_memberships')->whereIn('class_id', $classes)->select('id'))->count() > 1000
            || DB::table('grade_events')->whereIn('submission_id', DB::table('submissions')->whereIn('assignment_id', DB::table('assignments')->whereIn('class_id', $classes)->select('id'))->select('id'))->count() > 1000
            || DB::table('announcement_comment_audits')->whereIn('comment_id', DB::table('announcement_comments')->whereIn('announcement_id', DB::table('announcements')->whereIn('class_id', $classes)->select('id'))->select('id'))->count() > 1000
            || DB::table('courses')->where('owner_id', $manifest['teacherId'])->count() > 20
            || DB::table('semesters')->where('owner_id', $manifest['teacherId'])->count() > 10
            || DB::table('assignments')->whereIn('class_id', $classes)->count() > 60
            || DB::table('inbox_notifications')->whereIn('recipient_id', $manifest['userIds'])->count() > 1000
            || $classes->count() > 6 || DB::table('class_sessions')->whereIn('class_id', $classes)->count() > 100
            || DB::table('announcements')->whereIn('class_id', $classes)->count() > 60
            || DB::table('announcement_comments')->whereIn('announcement_id', DB::table('announcements')->whereIn('class_id', $classes)->select('id'))->count() > 200
            || DB::table('submission_revisions')->whereIn('submission_id', DB::table('submissions')->whereIn('assignment_id', DB::table('assignments')->whereIn('class_id', $classes)->select('id'))->select('id'))->count() > 500;
    }

    private function clearWorkspace(array $manifest): void
    {
        $teacher = User::findOrFail($manifest['teacherId']);
        if ($teacher->email !== 'teacher@demo.attendance.test' || $teacher->role !== 'teacher') {
            throw new RuntimeException('Portfolio owner identity changed; reset refused.');
        }
        $classes = DB::table('classes')->where('owner_id', $teacher->id)->pluck('id');
        $sessions = DB::table('class_sessions')->whereIn('class_id', $classes)->pluck('id');
        $records = DB::table('attendance_records')->whereIn('session_id', $sessions)->pluck('id');
        DB::table('attendance_audits')->whereIn('record_id', $records)->delete();
        DB::table('attendance_records')->whereIn('id', $records)->delete();
        DB::table('attendance_rosters')->whereIn('window_id', DB::table('attendance_windows')->whereIn('session_id', $sessions)->select('id'))->delete();
        DB::table('attendance_windows')->whereIn('session_id', $sessions)->delete();
        DB::table('class_sessions')->whereIn('id', $sessions)->delete();
        DB::table('session_series')->whereIn('class_id', $classes)->delete();
        $assignments = DB::table('assignments')->whereIn('class_id', $classes)->pluck('id');
        $submissions = DB::table('submissions')->whereIn('assignment_id', $assignments)->pluck('id');
        $revisions = DB::table('submission_revisions')->whereIn('submission_id', $submissions)->pluck('id');
        DB::table('submissions')->whereIn('id', $submissions)->update(['latest_revision_id' => null, 'published_grade_publication_id' => null]);
        DB::table('grade_events')->whereIn('submission_id', $submissions)->delete();
        DB::table('grade_publications')->whereIn('submission_id', $submissions)->delete();
        DB::table('grades')->whereIn('revision_id', $revisions)->delete();
        DB::table('submission_files')->whereIn('revision_id', $revisions)->delete();
        DB::table('submission_revisions')->whereIn('id', $revisions)->delete();
        DB::table('submissions')->whereIn('id', $submissions)->delete();
        DB::table('assignment_files')->whereIn('assignment_id', $assignments)->delete();
        if (DB::table('stored_files')->whereIn('class_id', $classes)->exists()) {
            throw new RuntimeException('Unexpected physical demo files; reset refused for manual inspection.');
        }
        DB::table('assignments')->whereIn('id', $assignments)->delete();
        $posts = DB::table('announcements')->whereIn('class_id', $classes)->pluck('id');
        DB::table('announcement_comment_audits')->whereIn('comment_id', DB::table('announcement_comments')->whereIn('announcement_id', $posts)->select('id'))->delete();
        DB::table('announcement_comments')->whereIn('announcement_id', $posts)->delete();
        DB::table('announcements')->whereIn('id', $posts)->delete();
        DB::table('membership_events')->whereIn('membership_id', DB::table('class_memberships')->whereIn('class_id', $classes)->select('id'))->delete();
        DB::table('class_memberships')->whereIn('class_id', $classes)->delete();
        DB::table('class_invites')->whereIn('class_id', $classes)->delete();
        DB::table('classes')->whereIn('id', $classes)->delete();
        DB::table('courses')->where('owner_id', $teacher->id)->delete();
        DB::table('semesters')->where('owner_id', $teacher->id)->delete();
        DB::table('outbox_events')->whereIn('event_key', DB::table('inbox_notifications')->whereIn('recipient_id', $manifest['userIds'])->select('event_key'))->delete();
        DB::table('inbox_notifications')->whereIn('recipient_id', $manifest['userIds'])->delete();
        DB::table('outbox_events')->whereIn('aggregate_id', [...$classes, ...$sessions, ...$assignments, ...$submissions, ...$posts])->delete();
        DB::table('account_preferences')->whereIn('user_id', $manifest['userIds'])->delete();
    }

    private function populate(User $teacher, Classroom $class, array $students, CarbonImmutable $now): void
    {
        foreach ([7, 5, 3, 1] as $days) {
            $start = $now->subDays($days)->setTime(1, 0);
            $session = ClassSession::create(['class_id' => $class->id, 'title' => ['Ôn tập và thực hành', 'Thiết kế mô hình dữ liệu', 'Thảo luận bài tập', 'Kiểm tra tiến độ'][$days % 4], 'room' => 'Phòng A'.count($students), 'starts_at' => $start, 'ends_at' => $start->addHour()]);
            $window = AttendanceWindow::create(['session_id' => $session->id, 'opened_by' => $teacher->id, 'opens_at' => $start, 'late_after' => $start->addMinutes(15), 'closes_at' => $start->addHour(), 'state' => 'closed']);
            foreach ($students as $position => $student) {
                AttendanceRoster::create(['window_id' => $window->id, 'student_id' => $student->id]);
                $status = match (($position + $days) % 10) {
                    8 => 'late',9 => 'absent',default => 'present'
                };
                $record = AttendanceRecord::create(['session_id' => $session->id, 'student_id' => $student->id, 'window_id' => $window->id, 'status' => $status, 'recorded_source' => 'manual', 'approved_at' => $days > 1 ? $start->addHours(2) : null, 'approved_by' => $days > 1 ? $teacher->id : null]);
                AttendanceAudit::create(['record_id' => $record->id, 'actor_id' => $teacher->id, 'action' => 'manual_correction', 'reason' => 'Điểm danh minh họa được seed; không phải lượt quét QR thật.', 'before_data' => null, 'after_data' => ['status' => $status, 'recordedSource' => 'manual', 'checkedInAt' => null, 'version' => 1]]);
            }
        }
        foreach ([0, 1, 4] as $days) {
            ClassSession::create(['class_id' => $class->id, 'title' => 'Thực hành và chữa bài', 'room' => 'Phòng A'.count($students), 'starts_at' => $now->addDays($days)->addHours(2), 'ends_at' => $now->addDays($days)->addHours(3)]);
        }
        foreach (range(1, 3) as $task) {
            $assignment = app(ManageAssignments::class)->save($teacher, $class, ['title' => ['Thiết kế trang giới thiệu', 'Xây dựng API quản lý lớp', 'Kiểm thử và báo cáo'][$task - 1], 'instructions' => 'Bài tập giả để xem giao diện; bài nộp minh họa chỉ có ghi chú, không có file thật.', 'dueAt' => $now->addDays($task)->toISOString(), 'allowLate' => true, 'allowResubmit' => true]);
            $assignment = app(ManageAssignments::class)->transition($teacher, $class, $assignment->id, $assignment->version, 'published');
            foreach ($students as $position => $student) {
                if ($position % 5 === 4 || ($task === 3 && $position === 0)) {
                    continue;
                }
                [, $revision] = app(ManageSubmissions::class)->submit($student, $class, $assignment->id, ['fileIds' => [], 'note' => 'Bài nộp minh họa chỉ có ghi chú; không phải bài làm thật.', 'idempotencyKey' => (string) Str::uuid()]);
                if ($position % 5 === 3) {
                    continue;
                }
                $grade = app(ManageGrades::class)->save($teacher, $revision->id, ['score' => 5 + (($position + $task) % 11) / 2, 'feedback' => 'Nhận xét minh họa: bài làm có tiến bộ.', 'expectedVersion' => 0]);
                if ($position % 5 !== 2) {
                    app(ManageGrades::class)->publish($teacher, $grade->id, ['expectedVersion' => $grade->version]);
                }
            }
        }
        app(ManageAssignments::class)->save($teacher, $class, ['title' => 'Đồ án cuối kỳ (đang soạn)', 'instructions' => 'Bản nháp chưa hiện với học sinh.', 'dueAt' => null, 'allowLate' => false, 'allowResubmit' => true]);
        $announcements = app(ManageAnnouncements::class);
        foreach (range(1, 3) as $index) {
            $post = $announcements->save($teacher, $class, ['title' => ['Chào mừng lớp học', 'Nhắc lịch học và bài tập', 'Nội dung đang soạn'][$index - 1].' · minh họa', 'body' => 'Bản tin minh họa của lớp. Vui lòng xem lịch học và bài tập trong các tab tương ứng.', 'pinned' => $index === 1]);
            if ($index === 3) {
                continue;
            }
            $post = $announcements->publish($teacher, $class, $post->id, $post->version);
            $announcements->addComment($teacher, $class, $post->id, 'Thông báo minh họa: thầy/cô đã cập nhật lịch học.');
            foreach (array_slice($students, 0, 3) as $i => $student) {
                $comment = $announcements->addComment($student, $class, $post->id, 'Bình luận minh họa: em đã xem thông báo.');
                if ($i === 1) {
                    $announcements->moderateComment($teacher, $class, $comment->id, true);
                }
                if ($i === 2) {
                    $announcements->deleteComment($teacher, $class, $comment->id);
                }
            }
        }
    }

    private function guard(): void
    {
        $test = app()->environment('testing') && in_array(config('database.connections.mysql.database'), ['attendance_test', 'attendance_ci_test'], true) && in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost'], true);
        $cloud = app()->environment('production') && getenv('RENDER_SERVICE_ID') === 'srv-dbh23o6c6qcc73csmo0g' && config('database.connections.mysql.host') === 'attendanceweb-mysql-free-dinhvantai079-e7d8.h.aivencloud.com';
        if (! config('demo.portfolio_enabled') || config('database.default') !== 'mysql' || config('mail.default') !== 'array' || (! $test && ! $cloud)) {
            throw new RuntimeException('Portfolio demo requires opt-in, array mail and the approved database.');
        }
    }
}
