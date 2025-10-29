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
        // Archiver les comptes bloqués expirés chaque jour à minuit
        $schedule->job(new \App\Jobs\ArchiveExpiredBlockedAccounts)
            ->daily()
            ->name('archive-expired-blocked-accounts')
            ->withoutOverlapping()
            ->runInBackground();

        // Débloquer les comptes dont le blocage est expiré chaque heure
        $schedule->job(new \App\Jobs\UnblockExpiredAccounts)
            ->hourly()
            ->name('unblock-expired-accounts')
            ->withoutOverlapping()
            ->runInBackground();
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
