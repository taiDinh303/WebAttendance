<?php
// Synthetic browser-test accounts only. Never run against dev/production.
$root = dirname(__DIR__);
if (! is_file($root.'/attendance_api/.env.http-smoke')) {
    throw new RuntimeException('Create an isolated .env.http-smoke and attendance_http_test database first.');
}
putenv('APP_ENV=http-smoke');
require $root.'/attendance_api/vendor/autoload.php';
$app = require $root.'/attendance_api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if ($app->environment() !== 'http-smoke' || config('database.connections.mysql.database') !== 'attendance_http_test' || config('mail.default') !== 'array') {
    throw new RuntimeException('Refusing to create fixtures outside the isolated HTTP test database with array mail.');
}
$credentials = [];
$suffix = bin2hex(random_bytes(6));
foreach (['teacherA' => ['teacher', 'Giáo viên kiểm thử A'], 'teacherB' => ['teacher', 'Giáo viên kiểm thử B'], 'student' => ['student', 'Học sinh kiểm thử']] as $key => [$role, $name]) {
    $password = 'Atd1!'.bin2hex(random_bytes(20));
    $user = App\Models\User::factory()->create(['email' => strtolower($key).'-'.$suffix.'@example.test', 'role' => $role, 'display_name' => $name, 'password' => $password]);
    $credentials[$key] = ['email' => $user->email, 'password' => $password, 'id' => $user->id];
}
if (! is_dir($root.'/work')) {
    mkdir($root.'/work', 0700, true);
}
file_put_contents($root.'/work/http-credentials.json', json_encode($credentials, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo "Created isolated synthetic accounts; random credentials saved only in ignored work/http-credentials.json. No real mail sent.\n";
