<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class EnvironmentReport
{
    public function inspect(): array
    {
        $key = (string) config('app.key', '');
        $keyBytes = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $checks = [
            'php_supported' => PHP_VERSION_ID >= 80300,
            'extensions_ready' => count(array_filter(['pdo_mysql', 'mbstring', 'fileinfo', 'intl', 'zip', 'openssl'], fn ($extension) => ! extension_loaded($extension))) === 0,
            'app_key_valid' => is_string($keyBytes) && strlen($keyBytes) === 32,
            'debug_disabled' => ! config('app.debug'),
            'mysql_selected' => config('database.default') === 'mysql',
            'private_storage_writable' => is_dir(storage_path('app/private')) && is_writable(storage_path('app/private')),
            'runtime_storage_writable' => is_writable(storage_path('framework')) && is_writable(storage_path('logs')),
        ];
        try {
            DB::select('SELECT 1');
            $checks['database_schema_ready'] = Schema::hasTable('admin_account_events') && Schema::hasTable('demo_datasets');
        } catch (Throwable) {
            $checks['database_schema_ready'] = false;
        }
        $production = app()->environment('production');
        $checks['demo_seed_disabled_in_production'] = ! $production || ! config('demo.enabled');
        $checks['https_in_production'] = ! $production || str_starts_with(config('app.url', ''), 'https://');
        $checks['secure_cookies_in_production'] = ! $production || config('session.secure') === true;
        $checks['encrypted_sessions_in_production'] = ! $production || config('session.encrypt') === true;
        $checks['file_scanner_configured_in_production'] = ! $production || config('filesafety.scanner') === 'clamav';
        $checks['mail_configured_in_production'] = ! $production || ! in_array(config('mail.default'), ['array', 'log', null], true);

        return ['environment' => app()->environment(), 'ready' => ! in_array(false, $checks, true), 'checks' => $checks,
            'scope' => 'Configuration, schema and local writable paths only. Does not verify delivery, antivirus daemon, TLS termination or production capacity.'];
    }
}
