<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('freedom-finger:sync --days=7')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();


Schedule::command('freedom-finger:refresh-machine-users')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
