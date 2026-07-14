<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sanctum:prune-expired --hours=24')
    ->dailyAt('02:15')
    ->withoutOverlapping();

Schedule::command('backup:run --quiet')
    ->dailyAt('02:30')
    ->withoutOverlapping();

Schedule::command('cash-control:archive-receipts --quiet')
    ->dailyAt('03:00')
    ->withoutOverlapping();
