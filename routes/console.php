<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\CihazSaglikKontrolu;
use App\Console\Commands\AkilliBildirimKontrolCommand;
use App\Console\Commands\ZamanlanmisBildirimGonder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command(CihazSaglikKontrolu::class)->everyFiveMinutes();
Schedule::command(AkilliBildirimKontrolCommand::class)->everyMinute();
Schedule::command(ZamanlanmisBildirimGonder::class)->everyMinute();

// 10.000 firma verisi ve sistemi için günlük gece yarısı yedekleme
Schedule::command('backup:run')->dailyAt('00:00')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('01:00');
