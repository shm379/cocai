<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// به‌روزرسانی روزانهٔ آرشیو نقشه‌ها (جدیدترین‌های هر منبع) + هش تصویر نقشه‌های جدید
Schedule::command('maps:update --sort=new --hash')
    ->dailyAt('04:30')
    ->withoutOverlapping(180)
    ->runInBackground();
