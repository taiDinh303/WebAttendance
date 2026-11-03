<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('demo:portfolio')->hourly()->withoutOverlapping()->when(fn () => config('demo.portfolio_enabled'));

Schedule::command('attendance:finalize')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
