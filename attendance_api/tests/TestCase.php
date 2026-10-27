<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = config('database.default');
        $database = config('database.connections.'.$connection.'.database');
        if ($connection !== 'mysql' || ! in_array($database, ['attendance_test', 'attendance_ci_test'], true)) {
            throw new \RuntimeException('Tests require an isolated MySQL attendance_test or attendance_ci_test database.');
        }

        return $app;
    }
}
