<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Timer;
use App\Services\TimerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TimerController extends Controller
{
    protected TimerService $timerService;

    public function __construct(TimerService $timerService)
    {
        $this->timerService = $timerService;
    }

    /**
     * Get the current timer for the authenticated user.
     */
    public function current(Request $request): JsonResponse
    {
        $timer = $this->timerService->getCurrentTimer($request->user());

        if (!$timer) {
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
}