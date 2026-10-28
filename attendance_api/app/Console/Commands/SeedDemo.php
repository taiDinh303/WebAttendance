<?php

namespace App\Console\Commands;

use App\Actions\Accounts\SeedDemoWorkspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('demo:seed')]
#[Description('Seed the isolated local demo workspace without resetting existing demo accounts')]
final class SeedDemo extends Command
{
    public function handle(SeedDemoWorkspace $demo): int
    {
        $result = $demo->handle();
        $directory = base_path('../work');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Cannot create ignored credential directory.');
        }
        $file = $directory.'/demo-accounts-'.app()->environment().'.json';
        if (! is_file($file) && $result['created']) {
            file_put_contents($file, json_encode([
                'notice' => 'Local synthetic demo only. Initial password; manual password/status changes are preserved on reseed.',
                'initialPassword' => config('demo.password'), 'accounts' => $result['accounts'],
                'classIds' => $result['classIds'], 'liveSessionId' => $result['liveSessionId'], 'liveWindowId' => $result['liveWindowId'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX);
        }
        $this->info($result['created'] ? 'Created 1 admin, 3 teachers, 90 students and 3 classes of 30 students.' : 'Demo already exists. Account passwords, statuses and classroom edits were preserved.');
        $this->line('Initial login details are stored only in the ignored work directory; no passwords are printed.');

        return self::SUCCESS;
    }
}
