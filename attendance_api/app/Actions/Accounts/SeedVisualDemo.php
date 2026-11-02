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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

final class SeedVisualDemo
{
    private const DATASET = 'attendance-visual-nine-classes-v1';

    public function handle(): array
    {
        $this->guard();

        return DB::transaction(function () {
            DB::table('demo_datasets')->insertOrIgnore(['name' => self::DATASET, 'manifest' => '{}', 'created_at' => now(), 'updated_at' => now()]);
            $dataset = DB::table('demo_datasets')->where('name', self::DATASET)->lockForUpdate()->first();
            $manifest = json_decode($dataset->manifest, true, 512, JSON_THROW_ON_ERROR);
            if ($manifest) {
                if (Classroom::whereIn('id', $manifest['classIds'])->count() !== 9 || User::whereIn('id', $manifest['studentIds'])->count() !== 180) {
                    throw new RuntimeException('Visual demo integrity changed; refusing to recreate or reset data.');
                }

                return [...$manifest, 'created' => false];
            }
            $password = config('demo.visual_password');
            if (! is_string($password) || strlen($password) < 11) {
                throw new RuntimeException('Set the approved demo password in server secrets.');
            }
            $hash = Hash::make($password);
            $now = CarbonImmutable::now('UTC')->startOfSecond();
            $classes = [];
            $studentIds = [];
            $teacherIds = [];
            $newNumber = 31;
            foreach (range(1, 3) as $number) {
                $teacher = User::where('email', sprintf('gv%03d@attendance.test', $number))->where('role', 'teacher')->lockForUpdate()->first();
                if (! $teacher) {
                    throw new RuntimeException('Expected existing synthetic teacher is missing; no new teacher was created.');
                }
                $owned = Classroom::where('owner_id', $teacher->id)->get();
                if ($owned->count() !== 1 || $owned[0]->status !== 'active') {
                    throw new RuntimeException('Expected one existing active demo class per teacher; refusing to alter other classes.');
                }
                $first = $owned[0];
                $oldStudents = User::whereIn('id', ClassMembership::where('class_id', $first->id)->where('status', 'active')->select('student_id'))->orderBy('email')->get();
                if ($oldStudents->count() !== 10 || $oldStudents->contains(fn ($u) => ! preg_match('/^hs\d{3}@attendance\.test$/', $u->email))) {
                    throw new RuntimeException('Existing roster is not the approved ten-student synthetic dataset.');
                }
                foreach ($oldStudents as $student) {
                    $n = (int) substr($student->email, 2, 3);
                    $student->student_code ??= sprintf('DEMO%04d', $n);
                    $student->gender ??= $n % 2 ? 'male' : 'female';
                    $student->birthday ??= '2005-'.sprintf('%02d', ($n % 12) + 1).'-'.sprintf('%02d', ($n % 27) + 1);
                    $student->avatar_key ??= 'blue';
                    if ($student->isDirty()) {
                        $student->version++;
                        $student->save();
                    }
                }
                $teacherIds[] = $teacher->id;
                $semester = app(SaveSemester::class)->handle($teacher, ['name' => 'Học kỳ minh họa 2026–2027', 'startDate' => $now->subMonth()->toDateString(), 'endDate' => $now->addMonths(4)->toDateString(), 'isActive' => true]);
                foreach ([10, 20, 30] as $index => $size) {
                    $subject = ['Lập trình Web', 'Cơ sở dữ liệu', 'Thiết kế giao diện'][$index];
                    $course = app(SaveCourse::class)->handle($teacher, ['name' => $subject, 'code' => 'MH-'.$number.'-'.($index + 1)]);
                    if ($size === 10) {
                        $class = $first;
                        $students = $oldStudents->all();
                        if (! $class->course_id) {
                            $class->course_id = $course->id;
                        }
                        if (! $class->semester_id) {
                            $class->semester_id = $semester->id;
                        }
                        if ($class->isDirty()) {
                            $class->version++;
                            $class->save();
                        }
                    } else {
                        [$class] = app(ManageClassroom::class)->create($teacher, ['name' => $subject.' · GV'.$number.' · '.$size.' học sinh', 'description' => 'Lớp minh họa do chủ dự án yêu cầu; toàn bộ dữ liệu là giả.', 'timezone' => 'Asia/Ho_Chi_Minh', 'courseId' => $course->id, 'semesterId' => $semester->id]);
                        $students = [];
                        for ($i = 0; $i < $size; $i++) {
                            $email = sprintf('hs%03d@attendance.test', $newNumber);
                            if (User::where('email', $email)->exists()) {
                                throw new RuntimeException('Synthetic student email collision; refusing overwrite.');
                            }
                            $student = User::forceCreate(['display_name' => ['Nguyễn Minh An', 'Trần Thu Hà', 'Lê Hoàng Nam', 'Phạm Ngọc Linh', 'Võ Gia Huy'][$newNumber % 5].sprintf(' %03d', $newNumber), 'email' => $email, 'role' => 'student', 'password' => $hash, 'email_verified_at' => $now, 'student_code' => sprintf('DEMO%04d', $newNumber), 'gender' => $newNumber % 2 ? 'male' : 'female', 'birthday' => '2005-'.sprintf('%02d', ($newNumber % 12) + 1).'-'.sprintf('%02d', ($newNumber % 27) + 1), 'avatar_key' => ['blue', 'green', 'amber', 'rose', 'violet'][$newNumber % 5]]);
                            $newNumber++;
                            $students[] = $student;
                            ClassMembership::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => $now, 'reviewed_at' => $now, 'joined_at' => $now, 'reviewed_by' => $teacher->id, 'review_note' => 'Dữ liệu minh họa được chủ dự án cho phép.']);
                        }
                        if ($size === 20) {
                            foreach (array_slice($oldStudents->all(), 0, 2) as $pending) {
                                ClassMembership::create(['class_id' => $class->id, 'student_id' => $pending->id, 'status' => 'pending', 'requested_at' => $now]);
                            }
                        }
                    }
                    $classes[] = $class->id;
                    foreach ($students as $student) {
                        $studentIds[] = $student->id;
                    }
                    $this->populate($teacher, $class, $students, $now);
                }
            }
            if (count(array_unique($studentIds)) !== 180) {
                throw new RuntimeException('Demo students overlap; transaction cancelled.');
            }
            $manifest = ['classIds' => $classes, 'teacherIds' => $teacherIds, 'studentIds' => $studentIds, 'classCount' => 9, 'studentCount' => 180];
            DB::table('demo_datasets')->where('name', self::DATASET)->update(['manifest' => json_encode($manifest, JSON_THROW_ON_ERROR), 'updated_at' => now()]);

            return [...$manifest, 'created' => true];
        });
    }

    private function populate(User $teacher, Classroom $class, array $students, CarbonImmutable $now): void
    {
        foreach ([7, 5, 3, 1] as $days) {
            $start = $now->subDays($days)->setTime(1, 0);
            $session = ClassSession::create(['class_id' => $class->id, 'title' => 'Buổi minh họa '.$days, 'room' => 'Phòng A'.count($students), 'starts_at' => $start, 'ends_at' => $start->addHour()]);
            $window = AttendanceWindow::create(['session_id' => $session->id, 'opened_by' => $teacher->id, 'opens_at' => $start, 'late_after' => $start->addMinutes(15), 'closes_at' => $start->addHour(), 'state' => 'closed']);
            foreach ($students as $position => $student) {
                AttendanceRoster::create(['window_id' => $window->id, 'student_id' => $student->id]);
                $status = match (($position + $days) % 10) {
                    8 => 'late',9 => 'absent',default => 'present'
                };
                $record = AttendanceRecord::create(['session_id' => $session->id, 'student_id' => $student->id, 'window_id' => $window->id, 'status' => $status, 'recorded_source' => 'manual']);
                AttendanceAudit::create(['record_id' => $record->id, 'actor_id' => $teacher->id, 'action' => 'manual_correction', 'reason' => 'Điểm danh minh họa được seed; không phải lượt quét QR thật.', 'before_data' => null, 'after_data' => ['status' => $status, 'recordedSource' => 'manual', 'checkedInAt' => null, 'version' => 1]]);
            }
        }
        foreach ([0, 1, 4] as $days) {
            ClassSession::create(['class_id' => $class->id, 'title' => 'Buổi học sắp tới · minh họa', 'room' => 'Phòng A'.count($students), 'starts_at' => $now->addDays($days)->addHours(2), 'ends_at' => $now->addDays($days)->addHours(3)]);
        }
        foreach (range(1, 3) as $task) {
            $assignment = app(ManageAssignments::class)->save($teacher, $class, ['title' => 'Bài thực hành '.$task.' · minh họa', 'instructions' => 'Bài tập giả để xem giao diện; bài nộp minh họa chỉ có ghi chú, không có file thật.', 'dueAt' => $now->addDays($task)->toISOString(), 'allowLate' => true, 'allowResubmit' => true]);
            $assignment = app(ManageAssignments::class)->transition($teacher, $class, $assignment->id, $assignment->version, 'published');
            foreach ($students as $position => $student) {
                if ($position % 5 === 4) {
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
        app(ManageAssignments::class)->save($teacher, $class, ['title' => 'Đề bài đang soạn · minh họa', 'instructions' => 'Bản nháp chưa hiện với học sinh.', 'dueAt' => null, 'allowLate' => false, 'allowResubmit' => true]);
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
        if (! config('demo.visual_enabled') || config('database.default') !== 'mysql' || config('mail.default') !== 'array' || (! $test && ! $cloud)) {
            throw new RuntimeException('Visual seed requires explicit opt-in, array mail and the approved isolated test or AttendanceWeb cloud database.');
        }
    }
}
