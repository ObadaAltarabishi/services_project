<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
protected function schedule(Schedule $schedule)
{
    // This is the correct way - use the injected $schedule instance
$schedule->command('orders:cancel-expired')->everyMinute();
    
    // For debugging only (remove in production)
    $nextRun = $schedule->events()[0]->nextRunDate()->format('Y-m-d H:i:s');
    \Log::info("Next execution at: {$nextRun}");
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
