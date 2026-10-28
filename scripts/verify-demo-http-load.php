<?php

// Real local HTTP requests, cookie sessions, CSRF, authorization and throttles included.
// Never target a remote deployment or reuse legacy credentials.
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__);
putenv('APP_ENV=demo');
require $root.'/attendance_api/vendor/autoload.php';
$app = require $root.'/attendance_api/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('demo') || config('database.connections.mysql.database') !== 'attendance_demo' || config('mail.default') !== 'array' || ! config('demo.enabled')) {
    throw new RuntimeException('Requires the isolated, opt-in local demo environment.');
}
$fixture = json_decode(file_get_contents($root.'/work/demo-accounts-demo.json'), true, 512, JSON_THROW_ON_ERROR);
$directory = $root.'/work/demo-http-'.bin2hex(random_bytes(6));
mkdir($directory, 0700, true);
const BASE = 'http://127.0.0.1:8000';

function handle(string $jar, string $method, string $path, ?array $body = null, ?string $interface = null): CurlHandle
{
    $headers = ['Accept: application/json', 'Origin: http://127.0.0.1:5173', 'Referer: http://127.0.0.1:5173/'];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        foreach (is_file($jar) ? file($jar, FILE_IGNORE_NEW_LINES) : [] as $line) {
            $parts = explode("\t", $line);
            if (count($parts) === 7 && $parts[5] === 'XSRF-TOKEN') {
                $headers[] = 'X-XSRF-TOKEN: '.rawurldecode($parts[6]);
            }
        }
    }
    $handle = curl_init(BASE.$path);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 90, CURLOPT_PROXY => '', CURLOPT_FOLLOWLOCATION => false]);
    if ($interface !== null) {
        curl_setopt($handle, CURLOPT_INTERFACE, $interface);
    }
    if ($body !== null) {
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
    }

    return $handle;
}
function request(string $jar, string $method, string $path, ?array $body = null, ?string $interface = null, int $attempt = 0): array
{
    $handle = handle($jar, $method, $path, $body, $interface);
    $retryAfter = 60;
    curl_setopt($handle, CURLOPT_HEADERFUNCTION, function ($handle, string $line) use (&$retryAfter): int {
        if (preg_match('/^Retry-After:\s*(\d+)/i', $line, $match)) {
            $retryAfter = (int) $match[1];
        }

        return strlen($line);
    });
    $raw = curl_exec($handle);
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_setopt($handle, CURLOPT_COOKIELIST, 'FLUSH');
    if ($status === 429 && $path === '/api/v1/auth/login' && $attempt < 2 && $retryAfter <= 60) {
        echo 'Login preparation respects rate limit; waiting '.max(1, $retryAfter)." seconds.\n";
        sleep(max(1, $retryAfter));

        return request($jar, $method, $path, $body, $interface, $attempt + 1);
    }
    if ($raw === false || $status < 200 || $status >= 300) {
        $code = is_string($raw) ? (json_decode($raw, true)['code'] ?? 'NON_JSON') : 'TRANSPORT';
        throw new RuntimeException("HTTP $method $path failed: $status $code. No credentials or response contents logged.");
    }

    return $raw === '' ? [] : json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
}
function login(string $jar, string $email, string $password, ?string $interface = null): void
{
    request($jar, 'GET', '/sanctum/csrf-cookie', null, $interface);
    request($jar, 'POST', '/api/v1/auth/login', ['email' => $email, 'password' => $password], $interface);
}
function batch(array $jars, string $token): array
{
    $multi = curl_multi_init();
    $handles = [];
    foreach ($jars as $jar) {
        $handle = handle($jar, 'POST', '/api/v1/attendance/check-ins', ['qrToken' => $token]);
        curl_multi_add_handle($multi, $handle);
        $handles[] = $handle;
    }
    $started = microtime(true);
    do {
        $status = curl_multi_exec($multi, $running);
        if ($status !== CURLM_OK) {
            throw new RuntimeException('Concurrent HTTP transport failed.');
        }
        if ($running > 0 && curl_multi_select($multi, 0.1) === -1) {
            usleep(1000);
        }
    } while ($running > 0);
    $result = [];
    foreach ($handles as $handle) {
        $response = json_decode(curl_multi_getcontent($handle), true);
        $result[] = ['status' => curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
            'durationMs' => round(curl_getinfo($handle, CURLINFO_TOTAL_TIME) * 1000, 2),
            'id' => $response['data']['id'] ?? null,
            'studentId' => $response['data']['studentId'] ?? null];
        curl_multi_remove_handle($multi, $handle);
    }
    curl_multi_close($multi);

    return ['durationMs' => round((microtime(true) - $started) * 1000, 2), 'requests' => $result];
}

// Teacher preparation uses a separate real loopback source address; students share
// 127.0.0.1 and remain subject to the existing 30 logins/minute/IP limit.
$teacherJar = $directory.'/teacher.cookies';
login($teacherJar, 'teacher01@demo.attendance.test', $fixture['initialPassword'], '127.0.0.2');
$jars = [];
for ($index = 1; $index <= 30; $index++) {
    $jar = $directory.'/student-'.$index.'.cookies';
    login($jar, 'student'.str_pad((string) $index, 3, '0', STR_PAD_LEFT).'@demo.attendance.test', $fixture['initialPassword']);
    $jars[] = $jar;
}
$classId = $fixture['classIds'][0];
$session = request($teacherJar, 'POST', '/api/v1/classes/'.$classId.'/sessions', [
    'title' => 'Kiểm tra 30 HTTP đồng thời '.gmdate('Y-m-d H:i:s'),
    'startsAt' => gmdate('c', time() - 60), 'endsAt' => gmdate('c', time() + 600), 'allowConflicts' => true,
])['data'];
$window = request($teacherJar, 'POST', '/api/v1/classes/'.$classId.'/sessions/'.$session['id'].'/attendance-windows', [
    'opensAt' => gmdate('c', time() - 60), 'lateAfter' => gmdate('c', time() + 300), 'closesAt' => gmdate('c', time() + 600),
])['data'];
$qrPath = '/api/v1/attendance-windows/'.$window['id'].'/qr';
$first = batch($jars, request($teacherJar, 'GET', $qrPath)['data']['qrToken']);
$replay = batch($jars, request($teacherJar, 'GET', $qrPath)['data']['qrToken']);
$records = request($teacherJar, 'GET', '/api/v1/classes/'.$classId.'/attendance?sessionId='.$session['id'].'&pageSize=100')['data'];
$ids = array_column($first['requests'], 'id');
$durations = array_column($first['requests'], 'durationMs');
sort($durations);
$passed = array_column($first['requests'], 'status') === array_fill(0, 30, 201)
    && array_column($replay['requests'], 'status') === array_fill(0, 30, 200)
    && ! in_array(null, $ids, true) && count(array_unique($ids)) === 30
    && $ids === array_column($replay['requests'], 'id')
    && $records['totalItems'] === 30 && count(array_unique(array_column($records['items'], 'studentId'))) === 30;
$summary = ['verifiedAt' => gmdate('c'), 'passed' => $passed, 'transport' => 'HTTP/cURL multi',
    'target' => BASE, 'environment' => 'demo', 'database' => 'attendance_demo', 'clients' => 30,
    'firstStatuses' => array_count_values(array_column($first['requests'], 'status')),
    'replayStatuses' => array_count_values(array_column($replay['requests'], 'status')),
    'uniqueRecords' => count(array_unique($ids)), 'persistedRecords' => $records['totalItems'],
    'sameRecordOnEveryReplay' => $ids === array_column($replay['requests'], 'id'),
    'batchDurationMs' => $first['durationMs'], 'p50Ms' => $durations[14], 'p95Ms' => $durations[28],
    'scope' => '30 dispatched HTTP requests with real auth/CSRF/permissions/throttles and MySQL. Local PHP development server queues requests; this is not a production throughput/SLA benchmark.'];
file_put_contents($root.'/work/demo-http-load.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL);
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
exit($passed ? 0 : 1);
