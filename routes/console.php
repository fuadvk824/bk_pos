<?php

// use Illuminate\Support\Facades\Schedule;

// Schedule::command('data:sync')
//     ->dailyAt('12:00')
//     ->withoutOverlapping();


use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
