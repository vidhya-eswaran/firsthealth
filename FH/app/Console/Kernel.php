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
        //$schedule->command('subscriptions:notify-expired')->dailyAt('00:01');
        $schedule->command('subscriptions:notify-expired')->daily()->sendOutputTo(storage_path('logs/subscription_notifications.log'));
        $schedule->command('notify:qualifying-period')->daily();
        $schedule->command('subscriptions:renew')->daily();
        $schedule->command('update:qualifying-period')->daily();
        $schedule->command('update:plan-expired')->daily();
        $schedule->command('cron:insert-non-emergency-trips')->everyMinute();
        $schedule->command('queue:work --stop-when-empty')->everyMinute();
        //$schedule->command('test:run')->everyMinute();
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
