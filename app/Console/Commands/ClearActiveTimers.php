<?php

namespace App\Console\Commands;

use App\Models\ActiveTimer;
use App\Models\User;
use Illuminate\Console\Command;

class ClearActiveTimers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timers:clear-active {--user= : User ID or email to clear timers for} {--all : Clear all active timers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear active timers for a specific user or all users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('all')) {
            if ($this->confirm('Are you sure you want to clear ALL active timers? This cannot be undone.')) {
                $count = ActiveTimer::count();
                ActiveTimer::truncate();
                $this->info("Successfully cleared {$count} active timer(s).");
            } else {
                $this->info('Operation cancelled.');
            }
            return Command::SUCCESS;
        }

        $userIdentifier = $this->option('user');
        if (!$userIdentifier) {
            $this->error('Please specify a user with --user=ID or --user=email, or use --all to clear all timers.');
            return Command::FAILURE;
        }

        // Find user by ID or email
        $user = is_numeric($userIdentifier) 
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (!$user) {
            $this->error("User not found: {$userIdentifier}");
            return Command::FAILURE;
        }

        $timer = ActiveTimer::where('user_id', $user->id)->first();
        
        if (!$timer) {
            $this->info("No active timer found for user: {$user->email}");
            return Command::SUCCESS;
        }

        if ($this->confirm("Clear active timer for {$user->email}? Started at: {$timer->started_at}")) {
            $timer->delete();
            $this->info("Successfully cleared active timer for {$user->email}");
        } else {
            $this->info('Operation cancelled.');
        }

        return Command::SUCCESS;
    }
}