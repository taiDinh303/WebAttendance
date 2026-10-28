<?php

namespace Tests\Feature;

use App\Support\EnvironmentReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EnvironmentDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostics_confirm_migrated_test_schema_without_exposing_credentials(): void
    {
        config(['app.debug' => false, 'demo.enabled' => false]);
        $result = app(EnvironmentReport::class)->inspect();
        $this->assertTrue($result['checks']['database_schema_ready']);
        $this->assertTrue($result['ready']);
        $json = json_encode($result, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString((string) config('app.key'), $json);
        $this->assertArrayNotHasKey('password', $result);
        $this->artisan('app:diagnose', ['--json' => true])->assertSuccessful();
    }

    public function test_production_preflight_refuses_insecure_demo_configuration(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['demo.enabled' => true, 'app.debug' => true, 'app.url' => 'http://example.test', 'session.secure' => false, 'filesafety.scanner' => 'local_validation', 'mail.default' => 'array']);
        $result = app(EnvironmentReport::class)->inspect();
        $this->assertFalse($result['ready']);
        foreach (['debug_disabled', 'demo_seed_disabled_in_production', 'https_in_production', 'secure_cookies_in_production', 'file_scanner_configured_in_production', 'mail_configured_in_production'] as $check) {
            $this->assertFalse($result['checks'][$check], $check);
        }
        $this->artisan('app:diagnose', ['--json' => true])->assertFailed();
    }

    public function test_missing_app_key_fails_readiness_without_throwing_or_printing_secrets(): void
    {
        config(['app.key' => null]);
        $result = app(EnvironmentReport::class)->inspect();
        $this->assertFalse($result['checks']['app_key_valid']);
        $this->assertFalse($result['ready']);
    }
}
