<?php

namespace Database\Seeders;

use App\Actions\Accounts\SeedDemoWorkspace;
use Illuminate\Database\Seeder;

final class DemoWorkspaceSeeder extends Seeder
{
    public function run(SeedDemoWorkspace $demo): void
    {
        $demo->handle();
    }
}
