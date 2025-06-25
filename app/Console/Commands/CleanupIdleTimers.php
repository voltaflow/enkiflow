<?php

namespace App\Console\Commands;

use App\Services\ActiveTimerService;
use Illuminate\Console\Command;

class CleanupIdleTimers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timers:cleanup-idle {--minutes=480 : Minutes of inactivity before considering a timer idle}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up idle timers by pausing them after a period of inactivity';

    protected ActiveTimerService $timerService;

    /**
     * Create a new command instance.
     */
    public function __construct(ActiveTimerService $timerService)
    {
        parent::__construct();
        $this->timerService = $timerService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $idleMinutes = (int) $this->option('minutes');
        
        $this->info("Checking for timers idle for more than {$idleMinutes} minutes...");
        
        $count = $this->timerService->cleanupIdleTimers($idleMinutes);
        
        if ($count > 0) {
            $this->info("Successfully paused {$count} idle timer(s).");
        } else {
            $this->info('No idle timers found.');
        }
        
        return Command::SUCCESS;
    }
}