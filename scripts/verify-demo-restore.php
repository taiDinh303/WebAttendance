<?php

// A local rehearsal into a NEW schema and directory. Never overwrites or drops data.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__);
putenv('APP_ENV=demo');
require $root.'/attendance_api/vendor/autoload.php';
$app = require $root.'/attendance_api/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('demo') || config('database.connections.mysql.database') !== 'attendance_demo'
    || ! in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost'], true)
    || config('mail.default') !== 'array') {
    throw new RuntimeException('Local isolated demo only.');
}
$runId = gmdate('Ymd_His').'_'.bin2hex(random_bytes(3));
$schema = 'attendance_restore_'.$runId;
$directory = $root.'/work/restore-'.$runId;
mkdir($directory, 0700, true);
$binCandidates = glob($root.'/.tools/mysql-package/mysql-*/bin/mysqldump.exe');
if (count($binCandidates) !== 1) {
    throw new RuntimeException('Expected one portable local MySQL toolchain.');
}
$bin = dirname($binCandidates[0]);
$dump = new Process([$bin.'/mysqldump.exe', '--defaults-extra-file='.$root.'/.tools/demo-db.cnf',
    '--single-transaction', '--no-tablespaces', '--set-gtid-purged=OFF', '--result-file='.$directory.'/database.sql', 'attendance_demo']);
$dump->setTimeout(120)->mustRun();
$local = parse_ini_file($root.'/.tools/mysql-admin.cnf', true, INI_SCANNER_RAW)['client'];
if (! in_array($local['host'] ?? '', ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Restore administrator must use loopback.');
}
$pdo = new PDO('mysql:host='.$local['host'].';port='.$local['port'].';charset=utf8mb4', $local['user'], $local['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
// Identifier is generated entirely here; CREATE without IF NOT EXISTS refuses collisions.
$pdo->exec('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$restore = new Process([$bin.'/mysql.exe', '--defaults-extra-file='.$root.'/.tools/mysql-admin.cnf', '--database='.$schema]);
$restore->setInput(fopen($directory.'/database.sql', 'rb'));
$restore->setTimeout(120)->mustRun();
$pdo->exec('USE `'.$schema.'`');
$counts = [];
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
    $original = DB::table($table)->count();
    $copy = (int) $pdo->query('SELECT COUNT(*) FROM `'.str_replace('`', '``', $table).'`')->fetchColumn();
    if ($original !== $copy) {
        throw new RuntimeException('Restored row count mismatch for '.$table);
    }
    $counts[$table] = $copy;
}
$statement = $pdo->prepare('SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL');
$statement->execute([$schema]);
$foreignKeys = $statement->fetchAll(PDO::FETCH_ASSOC);
$quote = fn (string $id) => '`'.str_replace('`', '``', $id).'`';
foreach ($foreignKeys as $fk) {
    $table = $quote($fk['TABLE_NAME']);
    $column = $quote($fk['COLUMN_NAME']);
    $parent = $quote($fk['REFERENCED_TABLE_NAME']);
    $parentColumn = $quote($fk['REFERENCED_COLUMN_NAME']);
    $orphans = (int) $pdo->query("SELECT COUNT(*) FROM $table c LEFT JOIN $parent p ON c.$column=p.$parentColumn WHERE c.$column IS NOT NULL AND p.$parentColumn IS NULL")->fetchColumn();
    if ($orphans !== 0) {
        throw new RuntimeException('Foreign key integrity failed.');
    }
}
$files = $pdo->query('SELECT storage_key,sha256 FROM stored_files')->fetchAll(PDO::FETCH_ASSOC);
mkdir($directory.'/private-files', 0700);
foreach ($files as $index => $file) {
    $source = Storage::disk('local')->path($file['storage_key']);
    $target = $directory.'/private-files/'.$index.'.bin';
    if (! copy($source, $target) || ! hash_equals($file['sha256'], hash_file('sha256', $target))) {
        throw new RuntimeException('Private file restore hash mismatch.');
    }
}
$fixture = json_decode(file_get_contents($root.'/work/demo-accounts-demo.json'), true, 512, JSON_THROW_ON_ERROR);
$hashes = $pdo->query('SELECT password FROM users')->fetchAll(PDO::FETCH_COLUMN);
$validPasswords = count(array_filter($hashes, fn ($hash) => Hash::check($fixture['initialPassword'], $hash)));
$summary = ['verifiedAt' => gmdate('c'), 'passed' => true, 'source' => 'attendance_demo',
    'restoredSchema' => $schema, 'tablesCompared' => count($counts), 'rowCounts' => $counts,
    'foreignKeysChecked' => count($foreignKeys), 'orphans' => 0, 'privateFilesHashVerified' => count($files),
    'initialPasswordHashesVerified' => $validPasswords,
    'scope' => 'Local SQL and private-file copy/restore rehearsal, row counts, FK references and password hash checks. No production restore, HTTP smoke against restored schema, encryption/retention or RPO/RTO certification.'];
file_put_contents($root.'/work/demo-restore-result.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL);
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
