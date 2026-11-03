<?php

namespace App\Console\Commands;

use App\Actions\Accounts\PortfolioDemo;
use Illuminate\Console\Command;

final class MaintainPortfolioDemo extends Command
{
    protected $signature = 'demo:portfolio';

    protected $description = 'Initialize or maintain only the isolated recruiter demonstration workspace';

    public function handle(PortfolioDemo $demo): int
    {
        $manifest = $demo->handle();
        $this->info('Portfolio ready: '.count($manifest['classIds']).' classes; baseline '.$manifest['resetAt']);

        return self::SUCCESS;
    }
}
