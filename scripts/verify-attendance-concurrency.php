<?php

// Concurrent domain/DB verification only; HTTP and production capacity are separate gates.
use App\Actions\Attendance\ManageAttendance;
use App\Actions\Classes\ManageClassroom;
use App\Models\AttendanceRecord;
use App\Models\ClassMembership;
use App\Models\ClassSession;
use App\Models\User;
use App\Support\QrTokens;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__);
putenv('APP_ENV=http-smoke');
require $root.'/attendance_api/vendor/autoload.php';
$app = require $root.'/attendance_api/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('http-smoke') || config('database.connections.mysql.database') !== 'attendance_http_test' || config('mail.default') !== 'array') {
    throw new RuntimeException('Concurrency verification requires the isolated HTTP test database and array mail.');
}
if (($argv[1] ?? '') === '--worker') {
    $directory = $argv[2];
    $index = (int) $argv[3];
    $fixture = json_decode(file_get_contents($directory.'/fixture.json'), true, 512, JSON_THROW_ON_ERROR);
    $student = User::findOrFail($fixture['students'][$index]);
    file_put_contents($directory.'/ready-'.$index, 'ready');
    $deadline = microtime(true) + 60;
    while (! is_file($directory.'/go.json')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Concurrent start barrier timed out.');
        }
        usleep(2000);
        clearstatcache(true, $directory.'/go.json');
    }
    $token = json_decode(file_get_contents($directory.'/go.json'), true, 512, JSON_THROW_ON_ERROR)['token'];
    $start = microtime(true);
    [$record, $created] = app(ManageAttendance::class)->checkIn($student, $token, app(QrTokens::class));
    [$replay, $recreated] = app(ManageAttendance::class)->checkIn($student, $token, app(QrTokens::class));
    file_put_contents($directory.'/result-'.$index.'.json', json_encode([
        'startedAt' => $start, 'durationMs' => round((microtime(true) - $start) * 1000, 2),
        'created' => $created, 'sameRecordOnReplay' => $record->id === $replay->id, 'recreated' => $recreated,
    ], JSON_THROW_ON_ERROR));
    exit(0);
}

$runId = bin2hex(random_bytes(6));
$directory = $root.'/work/concurrency-'.$runId;
mkdir($directory, 0700, true);
$targetStudents = [];
$targetSession = null;
$targetWindow = null;
for ($teacherIndex = 0; $teacherIndex < 3; $teacherIndex++) {
    $teacher = User::factory()->create(['role' => 'teacher', 'display_name' => 'Capacity Teacher '.$teacherIndex, 'email' => 'capacity-teacher-'.$teacherIndex.'-'.$runId.'@example.test']);
    [$class] = app(ManageClassroom::class)->create($teacher, ['name' => 'Capacity class '.$teacherIndex.' '.$runId, 'timezone' => 'Asia/Ho_Chi_Minh']);
    for ($studentIndex = 0; $studentIndex < 30; $studentIndex++) {
        $student = User::factory()->create(['role' => 'student', 'display_name' => 'Capacity Student '.$teacherIndex.' '.$studentIndex, 'email' => 'capacity-student-'.$teacherIndex.'-'.$studentIndex.'-'.$runId.'@example.test']);
        ClassMembership::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => now(), 'joined_at' => now()]);
        if ($teacherIndex === 0) {
            $targetStudents[] = $student->id;
        }
    }
    if ($teacherIndex === 0) {
        $targetSession = ClassSession::create(['class_id' => $class->id, 'title' => 'Concurrent attendance', 'starts_at' => now()->startOfSecond(), 'ends_at' => now()->addHour()->startOfSecond()]);
        $targetWindow = app(ManageAttendance::class)->open($teacher, $class, $targetSession->id, ['opensAt' => now()->subMinute()->toISOString(), 'closesAt' => now()->addMinutes(10)->toISOString()]);
    }
}
file_put_contents($directory.'/fixture.json', json_encode(['students' => $targetStudents], JSON_THROW_ON_ERROR));
$workers = [];
try {
    for ($index = 0; $index < 30; $index++) {
        $worker = new Process([PHP_BINARY, __FILE__, '--worker', $directory, (string) $index], $root);
        $worker->setTimeout(90);
        $worker->start();
        $workers[] = $worker;
    }
    $deadline = microtime(true) + 60;
    while (count(glob($directory.'/ready-*')) !== 30) {
        foreach ($workers as $worker) {
            if ($worker->isTerminated() && ! $worker->isSuccessful()) {
                throw new RuntimeException('A worker failed before the concurrency barrier.');
            }
        }
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Not all 30 workers reached the concurrency barrier.');
        }
        usleep(10000);
    }
    $token = app(QrTokens::class)->issue($targetWindow)['qrToken'];
    file_put_contents($directory.'/go.tmp', json_encode(['token' => $token], JSON_THROW_ON_ERROR));
    rename($directory.'/go.tmp', $directory.'/go.json');
    foreach ($workers as $worker) {
        $worker->wait();
        if (! $worker->isSuccessful()) {
            throw new RuntimeException('A concurrent check-in worker failed. Inspect ignored worker output locally.');
        }
    }
    $results = array_map(fn ($path) => json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR), glob($directory.'/result-*.json'));
    $records = AttendanceRecord::where('session_id', $targetSession->id);
    $durations = array_column($results, 'durationMs');
    sort($durations);
    $starts = array_column($results, 'startedAt');
    $passed = count($results) === 30 && (clone $records)->count() === 30 && (clone $records)->distinct()->count('student_id') === 30;
    foreach ($results as $result) {
        $passed = $passed && $result['created'] && $result['sameRecordOnReplay'] && ! $result['recreated'];
    }
    $summary = [
        'scenario' => '3 teachers / 90 students; 30 students check in to one window with a process start barrier',
        'boundary' => 'Laravel action + real MySQL transactions, 30 PHP processes; excludes HTTP/session middleware and production hosting',
        'passed' => $passed, 'teachers' => 3, 'students' => 90, 'workers' => 30, 'records' => (clone $records)->count(),
        'duplicateRecords' => (clone $records)->count() - (clone $records)->distinct()->count('student_id'),
        'startSpreadMs' => round((max($starts) - min($starts)) * 1000, 2),
        'p50CheckInAndReplayMs' => $durations[14], 'p95CheckInAndReplayMs' => $durations[28], 'maxCheckInAndReplayMs' => max($durations),
    ];
    file_put_contents($root.'/work/attendance-concurrency.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
    exit($passed ? 0 : 1);
} finally {
    foreach ($workers as $worker) {
        if ($worker->isRunning()) {
            $worker->stop();
        }
    }
}
