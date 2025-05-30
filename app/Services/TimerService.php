<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Models\Timer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TimerService
{
    /**
     * Start a new timer for a user.
     *
     * @throws \Exception
     */
    public function start(User $user, array $data): Timer
    {
        // Check if user already has a running timer
        $runningTimer = Timer::forUser($user->id)->running()->first();

        if ($runningTimer) {
            throw new \Exception('You already have a running timer. Please stop it before starting a new one.');
        }

        return Timer::create([
            'user_id' => $user->id,
            'project_id' => $data['project_id'] ?? null,
            'task_id' => $data['task_id'] ?? null,
            'description' => $data['description'] ?? null,
            'started_at' => now(),
            'is_running' => true,
            'current_duration' => 0,
        ]);
    }

    /**
     * Stop a timer and create a time entry.
     *
     * @throws \Exception
     */
    public function stop(Timer $timer): TimeEntry
    {
        if (! $timer->is_running) {
            throw new \Exception('This timer is not running.');
        }

        return DB::transaction(function () use ($timer) {
            return $timer->stop();
        });
    }

    /**
     * Pause a running timer.
     *
     * @throws \Exception
     */
    public function pause(Timer $timer): Timer
    {
        if (! $timer->is_running) {
            throw new \Exception('This timer is not running.');
        }

        $timer->pause();

        return $timer;
    }

    /**
     * Resume a paused timer.
     *
     * @throws \Exception
     */
    public function resume(Timer $timer): Timer
    {
        if ($timer->is_running) {
            throw new \Exception('This timer is already running.');
        }

        $timer->resume();

        return $timer;
    }

    /**
     * Get the current timer for a user.
     */
    public function getCurrentTimer(User $user): ?Timer
    {
        return Timer::forUser($user->id)->with(['project', 'task'])->first();
    }

    /**
     * Update timer details (project, task, description).
     */
    public function update(Timer $timer, array $data): Timer
    {
        $timer->update([
            'project_id' => $data['project_id'] ?? $timer->project_id,
            'task_id' => $data['task_id'] ?? $timer->task_id,
            'description' => $data['description'] ?? $timer->description,
        ]);

        return $timer;
    }

    /**
     * Delete a timer without creating a time entry.
     */
    public function discard(Timer $timer): void
    {
        $timer->delete();
    }

    /**
     * Get active timers for all users in a tenant.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveTimers()
    {
        return Timer::running()->with(['user', 'project', 'task'])->get();
    }

    /**
     * Stop all running timers (useful for cleanup).
     *
     * @return int Number of timers stopped
     */
    public function stopAllRunning(): int
    {
        $count = 0;
        $runningTimers = Timer::running()->get();

        foreach ($runningTimers as $timer) {
            try {
                $this->stop($timer);
                $count++;
            } catch (\Exception $e) {
                // Log error but continue with other timers
                \Log::error('Failed to stop timer: '.$e->getMessage(), ['timer_id' => $timer->id]);
            }
        }

        return $count;
    }
}
