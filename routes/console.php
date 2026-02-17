<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orderbook:clean-history')->hourly();
Schedule::command('trades:aggregate')->everyMinute();
Schedule::command('binance:fetch-open-interest')->everyThirtySeconds();
Schedule::command('binance:clean-futures-history')->hourly();
Schedule::command('vpin:compute')->everyMinute();
