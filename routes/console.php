<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('discord:status')->everyFiveMinutes();
Schedule::command('chat:cleanup')->daily();
Schedule::command('world:cleanup-deleted')->weekly();
Schedule::command('db:backup')->dailyAt('03:00');
