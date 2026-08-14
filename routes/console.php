<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Reputación externa Google (Outscraper Places): 3×/día hora Madrid.
| Requiere que el VPS ejecute `php artisan schedule:run` cada minuto.
*/
Schedule::command('external-reputation:sync')
    ->timezone('Europe/Madrid')
    ->dailyAt('10:00')
    ->withoutOverlapping(30)
    ->name('external-reputation-sync-1000');

Schedule::command('external-reputation:sync')
    ->timezone('Europe/Madrid')
    ->dailyAt('17:00')
    ->withoutOverlapping(30)
    ->name('external-reputation-sync-1700');

Schedule::command('external-reputation:sync')
    ->timezone('Europe/Madrid')
    ->dailyAt('23:55')
    ->withoutOverlapping(30)
    ->name('external-reputation-sync-2355');
