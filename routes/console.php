<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('agenda:send-reminders')
    ->dailyAt('18:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->description('Pengingat ringkasan kerja siswa');
