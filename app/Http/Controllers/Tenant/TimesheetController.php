<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimesheetController extends Controller
{
    /**
     * Submit a timesheet for approval
     */
    public function submit(Request $request)
    {
        $request->validate([
            'week_start' => 'required|date',
            'week_end' => 'required|date|after:week_start',
        ]);

        $weekStart = Carbon::parse($request->week_start)->startOfDay();
        $weekEnd = Carbon::parse($request->week_end)->endOfDay();
        $userId = Auth::id();

        try {
            DB::beginTransaction();

            // Get all time entries for the week
            $entries = TimeEntry::where('user_id', $userId)
                ->whereBetween('started_at', [$weekStart, $weekEnd])
                ->whereNull('submitted_at')
                ->get();

            if ($entries->isEmpty()) {
                return response()->json([
                    'message' => 'No hay entradas de tiempo para enviar en este período'
                ], 422);
            }

            // Mark all entries as submitted
            TimeEntry::where('user_id', $userId)
                ->whereBetween('started_at', [$weekStart, $weekEnd])
                ->whereNull('submitted_at')
                ->update([
                    'submitted_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Hoja de tiempo enviada exitosamente',
                'submitted_entries' => $entries->count(),
                'total_hours' => $entries->sum('duration') / 3600,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al enviar la hoja de tiempo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a timesheet (for managers/supervisors)
     */
    public function approve(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'week_start' => 'required|date',
        ]);

        $weekStart = Carbon::parse($request->week_start)->startOfDay();
        $weekEnd = Carbon::parse($request->week_start)->endOfDay()->addDays(6);
        $approverId = Auth::id();

        // Check if user has permission to approve
        // TODO: Add proper permission check here

        try {
            DB::beginTransaction();

            // Get submitted entries
            $entries = TimeEntry::where('user_id', $request->user_id)
                ->whereBetween('started_at', [$weekStart, $weekEnd])
                ->whereNotNull('submitted_at')
                ->whereNull('approved_at')
                ->get();

            if ($entries->isEmpty()) {
                return response()->json([
                    'message' => 'No hay entradas enviadas para aprobar en este período'
                ], 422);
            }

            // Mark all entries as approved and locked
            TimeEntry::where('user_id', $request->user_id)
                ->whereBetween('started_at', [$weekStart, $weekEnd])
                ->whereNotNull('submitted_at')
                ->whereNull('approved_at')
                ->update([
                    'approved_at' => now(),
                    'approved_by_id' => $approverId,
                    'locked_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Hoja de tiempo aprobada exitosamente',
                'approved_entries' => $entries->count(),
                'total_hours' => $entries->sum('duration') / 3600,
                'approved_by' => Auth::user()->name,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al aprobar la hoja de tiempo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a timesheet (for managers/supervisors)
     */
    public function reject(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'week_start' => 'required|date',
            'reason' => 'required|string|max:500',
        ]);

        $weekStart = Carbon::parse($request->week_start)->startOfDay();
        $weekEnd = Carbon::parse($request->week_start)->endOfDay()->addDays(6);

        // Check if user has permission to reject
        // TODO: Add proper permission check here

        try {
            DB::beginTransaction();

            // Get submitted entries
            $entries = TimeEntry::where('user_id', $request->user_id)
                ->whereBetween('started_at', [$weekStart, $weekEnd])
                ->whereNotNull('submitted_at')
                ->whereNull('approved_at')
                ->get();

            if ($entries->isEmpty()) {
                return response()->json([
                    'message' => 'No hay entradas enviadas para rechazar en este período'
                ], 422);
            }

            // Reset submission status
            TimeEntry::where('user_id', $request->user_id)
                ->whereBetween('started_at', [$weekStart, $weekEnd])
                ->whereNotNull('submitted_at')
                ->whereNull('approved_at')
                ->update([
                    'submitted_at' => null
                ]);

            // TODO: Send notification to user with rejection reason

            DB::commit();

            return response()->json([
                'message' => 'Hoja de tiempo rechazada',
                'rejected_entries' => $entries->count(),
                'reason' => $request->reason,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al rechazar la hoja de tiempo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timesheet status for a specific week
     */
    public function status(Request $request)
    {
        $request->validate([
            'week_start' => 'required|date',
        ]);

        $weekStart = Carbon::parse($request->week_start)->startOfDay();
        $weekEnd = Carbon::parse($request->week_start)->endOfDay()->addDays(6);
        $userId = $request->user_id ?? Auth::id();

        $entries = TimeEntry::where('user_id', $userId)
            ->whereBetween('started_at', [$weekStart, $weekEnd])
            ->select('submitted_at', 'approved_at', 'approved_by_id', 'locked_at')
            ->get();

        $status = 'draft';
        $submittedAt = null;
        $approvedAt = null;
        $approvedBy = null;
        $isLocked = false;

        if ($entries->isNotEmpty()) {
            $submittedEntries = $entries->whereNotNull('submitted_at');
            if ($submittedEntries->isNotEmpty()) {
                $status = 'submitted';
                $submittedAt = $submittedEntries->first()->submitted_at;

                $approvedEntries = $submittedEntries->whereNotNull('approved_at');
                if ($approvedEntries->isNotEmpty()) {
                    $status = 'approved';
                    $approvedAt = $approvedEntries->first()->approved_at;
                    $approvedBy = $approvedEntries->first()->approvedBy;
                }

                $lockedEntries = $entries->whereNotNull('locked_at');
                if ($lockedEntries->isNotEmpty()) {
                    $isLocked = true;
                }
            }
        }

        return response()->json([
            'status' => $status,
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
            'approved_by' => $approvedBy,
            'is_locked' => $isLocked,
            'total_hours' => $entries->sum('duration') / 3600,
            'entries_count' => $entries->count(),
        ]);
    }
}