<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActiveTimer;
use App\Models\Timer;
use App\Services\ActiveTimerService;
use App\Services\TimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    protected TimerService $timerService;
    protected ActiveTimerService $activeTimerService;

    public function __construct(TimerService $timerService, ActiveTimerService $activeTimerService)
    {
        $this->timerService = $timerService;
        $this->activeTimerService = $activeTimerService;
    }

    /**
     * Get the current timer for the authenticated user.
     */
    public function current(Request $request): JsonResponse
    {
        $timer = $this->timerService->getCurrentTimer($request->user());

        if (! $timer) {
            return response()->json([
                'timer' => null,
                'message' => 'No active timer found',
            ]);
        }

        return response()->json([
            'timer' => [
                'id' => $timer->id,
                'description' => $timer->description,
                'project_id' => $timer->project_id,
                'task_id' => $timer->task_id,
                'started_at' => $timer->started_at,
                'is_running' => $timer->is_running,
                'total_duration' => $timer->total_duration,
                'project' => $timer->project,
                'task' => $timer->task,
            ],
        ]);
    }

    /**
     * Start a new timer.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $timer = $this->timerService->start($request->user(), $validated);

            return response()->json([
                'success' => true,
                'timer' => [
                    'id' => $timer->id,
                    'description' => $timer->description,
                    'project_id' => $timer->project_id,
                    'task_id' => $timer->task_id,
                    'started_at' => $timer->started_at,
                    'is_running' => $timer->is_running,
                    'total_duration' => $timer->total_duration,
                ],
                'message' => 'Timer started successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Stop a timer and create a time entry.
     */
    public function stop(Request $request, Timer $timer): JsonResponse
    {
        // Ensure the timer belongs to the authenticated user
        if ($timer->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $timeEntry = $this->timerService->stop($timer);

            return response()->json([
                'success' => true,
                'time_entry' => [
                    'id' => $timeEntry->id,
                    'duration' => $timeEntry->duration,
                    'formatted_duration' => $timeEntry->formatted_duration,
                    'description' => $timeEntry->description,
                    'project_id' => $timeEntry->project_id,
                    'task_id' => $timeEntry->task_id,
                    'started_at' => $timeEntry->started_at,
                    'ended_at' => $timeEntry->ended_at,
                ],
                'message' => 'Timer stopped and time entry created',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Pause a running timer.
     */
    public function pause(Request $request, Timer $timer): JsonResponse
    {
        // Ensure the timer belongs to the authenticated user
        if ($timer->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $timer = $this->timerService->pause($timer);

            return response()->json([
                'success' => true,
                'timer' => [
                    'id' => $timer->id,
                    'is_running' => $timer->is_running,
                    'total_duration' => $timer->total_duration,
                ],
                'message' => 'Timer paused',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Resume a paused timer.
     */
    public function resume(Request $request, Timer $timer): JsonResponse
    {
        // Ensure the timer belongs to the authenticated user
        if ($timer->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $timer = $this->timerService->resume($timer);

            return response()->json([
                'success' => true,
                'timer' => [
                    'id' => $timer->id,
                    'is_running' => $timer->is_running,
                    'started_at' => $timer->started_at,
                ],
                'message' => 'Timer resumed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update timer details.
     */
    public function update(Request $request, Timer $timer): JsonResponse
    {
        // Ensure the timer belongs to the authenticated user
        if ($timer->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'description' => 'nullable|string|max:255',
        ]);

        $timer = $this->timerService->update($timer, $validated);

        return response()->json([
            'success' => true,
            'timer' => [
                'id' => $timer->id,
                'description' => $timer->description,
                'project_id' => $timer->project_id,
                'task_id' => $timer->task_id,
            ],
            'message' => 'Timer updated',
        ]);
    }

    /**
     * Delete a timer without creating a time entry.
     */
    public function destroy(Request $request, Timer $timer): JsonResponse
    {
        // Ensure the timer belongs to the authenticated user
        if ($timer->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $this->timerService->discard($timer);

        return response()->json([
            'success' => true,
            'message' => 'Timer discarded',
        ]);
    }

    /**
     * Get the active timer for the authenticated user.
     */
    public function active(Request $request): JsonResponse
    {
        $timer = $this->activeTimerService->getCurrent($request->user());

        if (! $timer) {
            return response()->json([
                'timer' => null,
                'message' => 'No active timer found',
            ]);
        }

        // Check if the timer is running but has no duration and started long ago
        // This indicates a stale timer that should be cleaned up
        if ($timer->is_running && $timer->duration == 0 && $timer->started_at) {
            $minutesSinceStart = $timer->started_at->diffInMinutes(now());
            
            // If timer has been "running" for more than 8 hours with 0 duration, it's likely stale
            if ($minutesSinceStart > 480) {
                \Illuminate\Support\Facades\Log::warning('Cleaning up stale timer', [
                    'timer_id' => $timer->id,
                    'user_id' => $timer->user_id,
                    'started_at' => $timer->started_at,
                    'minutes_since_start' => $minutesSinceStart
                ]);
                
                // Delete the stale timer
                $timer->delete();
                
                return response()->json([
                    'timer' => null,
                    'message' => 'No active timer found',
                ]);
            }
        }

        return response()->json([
            'timer' => $timer->toClientArray(),
        ]);
    }

    /**
     * Start or sync an active timer.
     */
    public function startActive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $timer = $this->activeTimerService->start($request->user(), $validated);

            return response()->json([
                'success' => true,
                'timer' => $timer->toClientArray(),
                'message' => 'Timer started successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Stop the active timer and create a time entry.
     */
    public function stopActive(Request $request): JsonResponse
    {
        try {
            $timeEntry = $this->activeTimerService->stop($request->user());

            return response()->json([
                'success' => true,
                'time_entry' => [
                    'id' => $timeEntry->id,
                    'duration' => $timeEntry->duration,
                    'formatted_duration' => $timeEntry->formatted_duration,
                    'description' => $timeEntry->description,
                    'project_id' => $timeEntry->project_id,
                    'task_id' => $timeEntry->task_id,
                    'started_at' => $timeEntry->started_at,
                    'ended_at' => $timeEntry->ended_at,
                ],
                'message' => 'Timer stopped and time entry created',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error stopping active timer', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Pause the active timer.
     */
    public function pauseActive(Request $request): JsonResponse
    {
        try {
            $timer = $this->activeTimerService->pause($request->user());

            return response()->json([
                'success' => true,
                'timer' => $timer->toClientArray(),
                'message' => 'Timer paused',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Resume the active timer.
     */
    public function resumeActive(Request $request): JsonResponse
    {
        try {
            $timer = $this->activeTimerService->resume($request->user());

            return response()->json([
                'success' => true,
                'timer' => $timer->toClientArray(),
                'message' => 'Timer resumed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Sync timer state from client.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'description' => 'nullable|string|max:255',
            'is_running' => 'nullable|boolean',
            'is_paused' => 'nullable|boolean',
            'duration' => 'nullable|integer|min:0',
            'paused_duration' => 'nullable|integer|min:0',
            'started_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        try {
            $timer = $this->activeTimerService->sync($request->user(), $validated);

            return response()->json([
                'success' => true,
                'timer' => $timer->toClientArray(),
                'server_time' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update active timer details.
     */
    public function updateActive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $timer = $this->activeTimerService->update($request->user(), $validated);

            return response()->json([
                'success' => true,
                'timer' => $timer->toClientArray(),
                'message' => 'Timer updated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Discard the active timer without creating a time entry.
     */
    public function discardActive(Request $request): JsonResponse
    {
        try {
            $discarded = $this->activeTimerService->discard($request->user());

            return response()->json([
                'success' => $discarded,
                'message' => $discarded ? 'Timer discarded' : 'No timer to discard',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
