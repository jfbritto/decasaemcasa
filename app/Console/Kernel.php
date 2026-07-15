<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // TTL de 5 min no lock: processo morto no meio não trava as expirações
        // por 24h (default do withoutOverlapping) em shared hosting
        $schedule->command('inscriptions:expire-unpaid')->everyMinute()->withoutOverlapping(5);
        $schedule->command('queue:work database --max-time=55 --sleep=1 --tries=3')->everyMinute()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
