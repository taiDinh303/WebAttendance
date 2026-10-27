<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class HealthTest extends TestCase
{
    public function test_liveness_does_not_query_the_database(): void
    {
        DB::shouldReceive('select')->never();

        $this->getJson('/health/live')->assertOk()->assertJsonPath('data.status', 'alive');
    }

    public function test_readiness_returns_503_without_exposing_database_errors(): void
    {
        DB::shouldReceive('select')->once()->with('SELECT 1')->andThrow(new \RuntimeException('private connection details'));

        $this->getJson('/health/ready')->assertStatus(503)->assertJsonPath('code', 'SERVICE_UNAVAILABLE')->assertDontSee('private connection details');
    }

    public function test_readiness_rejects_a_database_without_the_required_schema(): void
    {
        Schema::shouldReceive('hasTable')->once()->with('users')->andReturn(false);
        DB::shouldReceive('select')->once()->with('SELECT 1')->andReturn([]);

        $this->getJson('/health/ready')->assertStatus(503)->assertJsonPath('code', 'SERVICE_UNAVAILABLE');
    }
}
