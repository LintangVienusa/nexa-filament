<?php

namespace App\Console;

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
        
        \App\Console\Commands\ApplyAttendanceRules::class,
        \App\Console\Commands\AutoCheckout::class,
        // Tambahkan command lain jika ada...
    ];

    /**
     * Definisikan jadwal Artisan command.
     */
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule)
    {
        // $schedule->command('attendance:delete-expired')
        //         ->hourly()
        //         ->between('8:00', '23:30')
        //         ->withoutOverlapping()
        //         ->evenInMaintenanceMode();
        $schedule->command('attendance:apply-rules')->dailyAt('00:36')
        ->appendOutputTo('/var/www/html/apps/logs_cron/schedule_attendance_rules.log');
        $schedule->command('app:auto-checkout')->twiceDaily(20, 23)
        ->appendOutputTo('/var/www/html/apps/logs_cron/auto_checkout.log');
        
    }

    protected function commands()
        {
            $this->load(__DIR__ . '/Commands');

            // Bisa juga register lewat routes/console.php
            require base_path('routes/console.php');
        }
}