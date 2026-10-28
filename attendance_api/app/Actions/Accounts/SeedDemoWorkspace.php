<?php

namespace App\Actions\Accounts;

use App\Actions\Assignments\ManageAssignments;
use App\Actions\Assignments\ManageGrades;
use App\Actions\Assignments\ManageSubmissions;
use App\Actions\Attendance\ManageAttendance;
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
use App\Models\StoredFile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class SeedDemoWorkspace
{
    private const DATASET = 'attendance-demo-v1';

    public function __construct(
        private readonly ManageClassroom $classes,
        private readonly ManageAttendance $attendance,
        private readonly ManageAssignments $assignments,
        private readonly ManageSubmissions $submissions,
        private readonly ManageGrades $grades,
        private readonly SaveCourse $courses,
        private readonly SaveSemester $semesters,
    ) {}

    public function handle(): array
    {
        $this->requireSafeEnvironment();
        $newStorageKeys = [];
        try {
            return DB::transaction(function () use (&$newStorageKeys) {
                DB::table('demo_datasets')->insertOrIgnore(['name' => self::DATASET, 'manifest' => '{}', 'created_at' => now(), 'updated_at' => now()]);
                $dataset = DB::table('demo_datasets')->where('name', self::DATASET)->lockForUpdate()->first();
                $manifest = json_decode($dataset->manifest, true, 512, JSON_THROW_ON_ERROR);
                if (! empty($manifest)) {
                    if (User::whereIn('id', array_column($manifest['accounts'], 'id'))->count() !== 94) {
                        throw new RuntimeException('Demo dataset integrity changed; refusing to recreate or overwrite accounts.');
                    }

                    return [...$manifest, 'created' => false];
                }
                if (User::where('email', 'like', '%@demo.attendance.test')->exists()) {
                    throw new RuntimeException('Demo namespace is already in use without its manifest; no accounts were modified.');
                }
                $hash = Hash::make(config('demo.password'));
                $accounts = [];
                $admin = $this->account('admin', 'admin', 'Quản trị Demo', $hash);
                $accounts[] = $this->identity($admin);
                $classIds = [];
                $now = CarbonImmutable::now('UTC')->startOfSecond();
                foreach (['Toán 10A1', 'Vật lý 10A2', 'Tiếng Anh 10A3'] as $index => $name) {
                    $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                    $teacher = $this->account('teacher'.$number, 'teacher', 'Giáo viên Demo '.$number, $hash);
                    $accounts[] = $this->identity($teacher);
                    $course = $this->courses->handle($teacher, ['name' => $name, 'code' => 'DEMO-'.$number]);
                    $semester = $this->semesters->handle($teacher, ['name' => 'Học kỳ Demo', 'startDate' => $now->subMonths(2)->toDateString(), 'endDate' => $now->addMonths(4)->toDateString(), 'isActive' => true]);
                    [$class] = $this->classes->create($teacher, ['name' => $name, 'description' => 'Lớp minh họa gồm 30 học sinh. Toàn bộ dữ liệu là giả.', 'timezone' => 'Asia/Ho_Chi_Minh', 'courseId' => $course->id, 'semesterId' => $semester->id]);
                    $classIds[] = $class->id;
                    $students = [];
                    for ($position = 1; $position <= 30; $position++) {
                        $studentNumber = str_pad((string) ($index * 30 + $position), 3, '0', STR_PAD_LEFT);
                        $student = $this->account('student'.$studentNumber, 'student', 'Học sinh Demo '.$studentNumber, $hash);
                        $students[] = $student;
                        $accounts[] = $this->identity($student);
                        ClassMembership::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => $now, 'reviewed_at' => $now, 'reviewed_by' => $teacher->id, 'joined_at' => $now, 'review_note' => 'Dữ liệu minh họa được tạo bằng seed local.']);
                    }
                    $this->attendanceHistory($teacher, $class, $students, $now);
                    $this->learningSamples($teacher, $class, $students, $now, $newStorageKeys);
                    $session = ClassSession::create(['class_id' => $class->id, 'title' => 'Buổi học Demo hôm nay', 'room' => 'Phòng Demo '.($index + 1), 'starts_at' => $now, 'ends_at' => $now->addHour()]);
                    ClassSession::create(['class_id' => $class->id, 'title' => 'Buổi học Demo tiếp theo', 'room' => 'Phòng Demo '.($index + 1), 'starts_at' => $now->addDay(), 'ends_at' => $now->addDay()->addHour()]);
                    if ($index === 0) {
                        $liveSession = $session;
                        $liveWindow = $this->attendance->open($teacher, $class, $session->id, ['opensAt' => $now->subMinute()->toISOString(), 'lateAfter' => $now->addMinutes(30)->toISOString(), 'closesAt' => $now->addHour()->toISOString()]);
                    }
                }
                $manifest = ['accounts' => $accounts, 'classIds' => $classIds, 'liveSessionId' => $liveSession->id, 'liveWindowId' => $liveWindow->id];
                DB::table('demo_datasets')->where('name', self::DATASET)->update(['manifest' => json_encode($manifest, JSON_THROW_ON_ERROR), 'updated_at' => now()]);

                return [...$manifest, 'created' => true];
            });
        } catch (Throwable $error) {
            foreach ($newStorageKeys as $key) {
                Storage::disk('local')->delete($key);
            }
            throw $error;
        }
    }

    private function requireSafeEnvironment(): void
    {
        $expected = ['demo' => ['attendance_demo'], 'local' => ['attendance_demo'], 'testing' => ['attendance_test', 'attendance_ci_test'], 'http-smoke' => ['attendance_http_test']];
        $environment = app()->environment();
        $database = config('database.connections.mysql.database');
        $password = config('demo.password');
        if (! config('demo.enabled') || config('database.default') !== 'mysql' || ! isset($expected[$environment]) || ! in_array($database, $expected[$environment], true) || ! in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true) || config('mail.default') !== 'array') {
            throw new RuntimeException('Demo seed requires explicit opt-in, an approved isolated database and array mail in a non-production environment.');
        }
        if (! is_string($password) || strlen($password) < 12 || ! preg_match('/[a-z]/', $password) || ! preg_match('/[A-Z]/', $password) || ! preg_match('/[0-9]/', $password)) {
            throw new RuntimeException('Set a strong DEMO_PASSWORD in the ignored environment file before seeding.');
        }
    }

    private function account(string $localPart, string $role, string $name, string $hash): User
    {
        $user = new User(['display_name' => $name, 'email' => $localPart.'@demo.attendance.test', 'password' => $hash]);
        $user->role = $role;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    private function identity(User $user): array
    {
        return ['id' => $user->id, 'email' => $user->email, 'role' => $user->role];
    }

    /** @param list<User> $students */
    private function attendanceHistory(User $teacher, Classroom $class, array $students, CarbonImmutable $now): void
    {
        foreach ([7, 3] as $daysAgo) {
            $start = $now->subDays($daysAgo);
            $session = ClassSession::create(['class_id' => $class->id, 'title' => 'Buổi Demo cách đây '.$daysAgo.' ngày', 'starts_at' => $start, 'ends_at' => $start->addHour()]);
            $window = AttendanceWindow::create(['session_id' => $session->id, 'opened_by' => $teacher->id, 'opens_at' => $start, 'late_after' => $start->addMinutes(15), 'closes_at' => $start->addHour(), 'state' => 'closed']);
            foreach ($students as $position => $student) {
                AttendanceRoster::create(['window_id' => $window->id, 'student_id' => $student->id]);
                $status = match ($position % 10) {
                    8 => 'late', 9 => 'absent', default => 'present'
                };
                $record = AttendanceRecord::create(['session_id' => $session->id, 'student_id' => $student->id, 'window_id' => $window->id, 'status' => $status, 'recorded_source' => 'manual']);
                AttendanceAudit::create(['record_id' => $record->id, 'actor_id' => $teacher->id, 'action' => 'manual_correction', 'reason' => 'Dữ liệu minh họa được tạo bằng seed local, không phải lượt quét QR thật.', 'before_data' => null, 'after_data' => ['status' => $status, 'recordedSource' => 'manual', 'checkedInAt' => null, 'version' => 1]]);
            }
        }
    }

    /** @param list<User> $students @param list<string> $newStorageKeys */
    private function learningSamples(User $teacher, Classroom $class, array $students, CarbonImmutable $now, array &$newStorageKeys): void
    {
        $homework = $this->assignments->save($teacher, $class, ['title' => 'Bài tập tuần mới · '.$class->name, 'instructions' => 'Đây là đề minh họa. Bạn có thể dùng để thử nộp file.', 'dueAt' => $now->addDays(5)->toISOString(), 'allowLate' => true, 'allowResubmit' => true]);
        $this->assignments->transition($teacher, $class, $homework->id, $homework->version, 'published');
        $sample = $this->assignments->save($teacher, $class, ['title' => 'Bài thực hành minh họa · '.$class->name, 'instructions' => 'Bài tập dùng để minh họa lịch sử nộp và điểm công bố.', 'dueAt' => $now->addDays(2)->toISOString(), 'allowLate' => true, 'allowResubmit' => true]);
        $sample = $this->assignments->transition($teacher, $class, $sample->id, $sample->version, 'published');
        $content = "Bai lam minh hoa AttendanceWeb. Day la du lieu gia, khong phai bai nop that.\n";
        foreach (array_slice($students, 0, 15) as $position => $student) {
            $key = 'demo/'.Str::ulid().'.txt';
            $newStorageKeys[] = $key;
            if (! Storage::disk('local')->put($key, $content)) {
                throw new RuntimeException('Unable to write private demo file.');
            }
            $file = StoredFile::create(['class_id' => $class->id, 'assignment_id' => $sample->id, 'uploaded_by' => $student->id, 'purpose' => 'submission', 'storage_key' => $key, 'original_name' => 'bai-lam-demo.txt', 'mime_type' => 'text/plain', 'size_bytes' => strlen($content), 'sha256' => hash('sha256', $content), 'scan_state' => 'validated_local']);
            [$submission, $revision] = $this->submissions->submit($student, $class, $sample->id, ['fileIds' => [$file->id], 'note' => 'Bài làm minh họa được seed local.', 'idempotencyKey' => (string) Str::uuid()]);
            if ($position < 13) {
                $grade = $this->grades->save($teacher, $revision->id, ['score' => 7.5 + ($position % 5) * 0.5, 'feedback' => 'Nhận xét minh họa, không phải kết quả học tập thật.', 'expectedVersion' => 0]);
                if ($position < 10) {
                    $this->grades->publish($teacher, $grade->id, ['expectedVersion' => $grade->version]);
                }
            }
        }
    }
}
