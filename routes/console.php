<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Automated Background Tasks / Scheduling Notes
|--------------------------------------------------------------------------
| Automatic Telegram Daily Reminder is permanently deactivated.
| Reminder Center operates independently without automated background sends.
*/

