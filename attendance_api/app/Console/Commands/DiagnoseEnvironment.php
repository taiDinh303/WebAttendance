<?php

namespace App\Console\Commands;

use App\Support\EnvironmentReport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:diagnose {--json : Print non-secret machine-readable checks}')]
#[Description('Check environment and migration readiness without exposing credentials')]
final class DiagnoseEnvironment extends Command
{
    public function handle(EnvironmentReport $report): int
    {
        $result = $report->inspect();
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            foreach ($result['checks'] as $name => $passed) {
                $this->line(($passed ? 'PASS ' : 'FAIL ').$name);
            }
            $this->line($result['scope']);
        }

        return $result['ready'] ? self::SUCCESS : self::FAILURE;
    }
}
