<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');




Schedule::command('alerts:generate')->hourly()->withoutOverlapping();
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put('workspace-scheduler-heartbeat', now()->toIso8601String(), now()->addDays(2)))->everyFiveMinutes()->name('workspace-heartbeat');
Schedule::command('workspace:backup --verify')->dailyAt('02:00')->when(fn () => config('assistant.backup_enabled'))->withoutOverlapping();
