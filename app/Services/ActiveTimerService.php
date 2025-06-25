<?php

namespace App\Services;

use App\Models\ActiveTimer;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActiveTimerService
{
    /**
     * Get or create active timer for user.
     */
    public function getOrCreate(User $user, array $data = []): ActiveTimer
    {
        return DB::transaction(function () use ($user, $data) {
            $timer = ActiveTimer::forUser($user->id)->first();

            if (! $timer) {
                $timer = ActiveTimer::create([
                    'user_id' => $user->id,
                    'project_id' => $data['project_id'] ?? null,
                    'task_id' => $data['task_id'] ?? null,
                    'description' => $data['description'] ?? '',
                    'started_at' => $data['started_at'] ?? now(),
                    'is_running' => $data['is_running'] ?? false,
                    'is_paused' => $data['is_paused'] ?? false,
                    'duration' => $data['duration'] ?? 0,
                    'paused_duration' => $data['paused_duration'] ?? 0,
                    'metadata' => $data['metadata'] ?? [],
                ]);
            }

            return $timer;
        });
    }

    /**
     * Start a new timer or restart existing one.
     */
    public function start(User $user, array $data = []): ActiveTimer
    {
        return DB::transaction(function () use ($user, $data) {
            // Get or create timer
            $timer = $this->getOrCreate($user);

            // If timer is already running, just update the data
            if ($timer->is_running) {
                return $timer->syncFromClient($data);
            }

            // Check if this is a stale timer (not running but exists)
            // This could happen if the stop process failed
            if (!$timer->is_running && $timer->started_at && $timer->started_at->diffInHours(now()) > 8) {
                Log::warning('Found stale timer, deleting before starting new one', [
                    'timer_id' => $timer->id,
                    'user_id' => $user->id,
                    'started_at' => $timer->started_at,
                ]);
                $timer->delete();
                
                // Create a fresh timer
                $timer = ActiveTimer::create([
                    'user_id' => $user->id,
                    'project_id' => $data['project_id'] ?? null,
                    'task_id' => $data['task_id'] ?? null,
                    'description' => $data['description'] ?? '',
                    'started_at' => now(),
                    'is_running' => true,
                    'is_paused' => false,
                    'duration' => 0,
                    'paused_duration' => 0,
                    'metadata' => $data['metadata'] ?? [],
                    'last_synced_at' => now(),
                ]);
                
                return $timer;
            }

            // Start fresh timer
            $timer->update([
                'project_id' => $data['project_id'] ?? null,
                'task_id' => $data['task_id'] ?? null,
                'description' => $data['description'] ?? '',
                'started_at' => now(),
                'is_running' => true,
                'is_paused' => false,
                'duration' => 0,
                'paused_duration' => 0,
                'metadata' => $data['metadata'] ?? [],
                'last_synced_at' => now(),
            ]);

            return $timer;
        });
    }

    /**
     * Stop timer and create time entry.
     */
    public function stop(User $user): ?TimeEntry
    {
        return DB::transaction(function () use ($user) {
            $timer = ActiveTimer::forUser($user->id)->first();

            if (! $timer) {
                // Log for debugging
                Log::warning('No active timer found for user when trying to stop', [
                    'user_id' => $user->id,
                ]);
                throw new \Exception('No active timer found.');
            }

            try {
                $timeEntry = $timer->stop();
                
                // Double-check that timer was deleted
                if (ActiveTimer::find($timer->id)) {
                    Log::error('Timer was not deleted after stop', [
                        'timer_id' => $timer->id,
                        'user_id' => $user->id,
                    ]);
                    // Force delete
                    ActiveTimer::where('id', $timer->id)->forceDelete();
                }
                
                return $timeEntry;
            } catch (\Exception $e) {
                Log::error('Error stopping timer', [
                    'timer_id' => $timer->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Pause the timer.
     */
    public function pause(User $user): ActiveTimer
    {
        $timer = ActiveTimer::forUser($user->id)->first();

        if (! $timer) {
            throw new \Exception('No active timer found.');
        }

        return $timer->pause();
    }

    /**
     * Resume the timer.
     */
    public function resume(User $user): ActiveTimer
    {
        $timer = ActiveTimer::forUser($user->id)->first();

        if (! $timer) {
            throw new \Exception('No active timer found.');
        }

        return $timer->resume();
    }

    /**
     * Sync timer state from client.
     */
    public function sync(User $user, array $data): ActiveTimer
    {
        return DB::transaction(function () use ($user, $data) {
            $timer = $this->getOrCreate($user, $data);

            // Sync the timer data
            $timer->syncFromClient($data);

            // Check for idle timeout
            if ($timer->isIdle(10)) {
                Log::warning('Timer has been idle for too long', [
                    'user_id' => $user->id,
                    'timer_id' => $timer->id,
                    'last_synced_at' => $timer->last_synced_at,
                ]);
            }

            return $timer;
        });
    }

    /**
     * Get current timer for user.
     */
    public function getCurrent(User $user): ?ActiveTimer
    {
        return ActiveTimer::forUser($user->id)
            ->with(['project', 'task'])
            ->first();
    }

    /**
     * Delete timer without creating time entry.
     */
    public function discard(User $user): bool
    {
        $timer = ActiveTimer::forUser($user->id)->first();

        if ($timer) {
            $timer->delete();
            return true;
        }

        return false;
    }

    /**
     * Clean up idle timers.
     */
    public function cleanupIdleTimers(int $idleMinutes = 480): int
    {
        $count = 0;
        $idleTimers = ActiveTimer::where('last_synced_at', '<', now()->subMinutes($idleMinutes))
            ->where('is_running', true)
            ->get();

        foreach ($idleTimers as $timer) {
            try {
                // Pause idle timers instead of stopping them
                $timer->pause();
                $timer->update([
                    'metadata' => array_merge($timer->metadata ?? [], [
                        'auto_paused_at' => now()->toIso8601String(),
                        'auto_paused_reason' => 'idle_timeout',
                    ]),
                ]);
                $count++;

                Log::info('Auto-paused idle timer', [
                    'timer_id' => $timer->id,
                    'user_id' => $timer->user_id,
                    'idle_minutes' => $timer->last_synced_at->diffInMinutes(now()),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to auto-pause idle timer', [
                    'timer_id' => $timer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Update timer details.
     */
    public function update(User $user, array $data): ActiveTimer
    {
        $timer = ActiveTimer::forUser($user->id)->first();

        if (! $timer) {
            throw new \Exception('No active timer found.');
        }

        $timer->update([
            'project_id' => $data['project_id'] ?? $timer->project_id,
            'task_id' => $data['task_id'] ?? $timer->task_id,
            'description' => $data['description'] ?? $timer->description,
            'last_synced_at' => now(),
        ]);

        return $timer;
    }

    /**
     * Get timer statistics for user.
     */
    public function getStats(User $user): array
    {
        $timer = ActiveTimer::forUser($user->id)->first();

        if (! $timer) {
            return [
                'has_active_timer' => false,
                'current_duration' => 0,
                'formatted_duration' => '00:00:00',
            ];
        }

        return [
            'has_active_timer' => true,
            'is_running' => $timer->is_running,
            'is_paused' => $timer->is_paused,
            'current_duration' => $timer->current_duration,
            'formatted_duration' => $timer->formatted_duration,
            'started_at' => $timer->started_at?->toIso8601String(),
            'project_name' => $timer->project?->name,
            'task_name' => $timer->task?->name,
        ];
    }
}