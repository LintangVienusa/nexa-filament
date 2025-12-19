<?php

namespace App\Console;

use App\Console\Commands\ApplyAttendanceRules;
use App\Console\Commands\AutoCheckout;
use App\Console\Commands\FixImageOrientation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Daftar command Artisan custom kamu.
     *
     * Misalnya kamu punya file:
     * app/Console/Commands/DeleteExpiredAttendance.php
     * maka masukkan ke sini.
     */
    protected $commands = [

        ApplyAttendanceRules::class,
        AutoCheckout::class,
        FixImageOrientation::class,
        // Tambahkan command lain jika ada...
    ];

    /**
     * Definisikan jadwal Artisan command.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('attendance:apply-rules')
            ->dailyAt('09:36')
            ->appendOutputTo('/var/www/html/apps/logs_cron/schedule_attendance_rules.log');
        $schedule->command('app:auto-checkout')
            ->twiceDaily(20, 23)
            ->appendOutputTo('/var/www/html/apps/logs_cron/auto_checkout.log');

    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        // Bisa juga register lewat routes/console.php
        require base_path('routes/console.php');
    }
}
