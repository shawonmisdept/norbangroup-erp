<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('hrm:sync-adms --queue')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('hrm:process-attendance --mark-absences --queue')
    ->dailyAt('23:30')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('hrm:notify-daily-attendance')
    ->dailyAt('23:45')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('hrm:notify-employment-milestones')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('hrm:notify-recruitment-interviews')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('hrm:close-expired-job-postings')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->runInBackground();
