<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Models\UserTimePreference;
use App\Notifications\DailyTimeReminder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ReminderController extends Controller
{
    /**
     * Send daily reminder notification
     */
    public function sendDaily(Request $request)
    {
        $request->validate([
            'hours_tracked' => 'required|numeric|min:0',
            'hours_goal' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $preferences = UserTimePreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'daily_hours_goal' => 8,
                'reminder_time' => '17:00:00',
                'enable_reminders' => true,
            ]
        );

        if (!$preferences->enable_reminders) {
            return response()->json([
                'message' => 'Los recordatorios están deshabilitados'
            ], 422);
        }

        try {
            // Send notification
            $user->notify(new DailyTimeReminder(
                $request->hours_tracked,
                $request->hours_goal
            ));

            // Update last reminder sent time
            $preferences->update([
                'last_reminder_sent_at' => now()
            ]);

            return response()->json([
                'message' => 'Recordatorio enviado exitosamente',
                'hours_tracked' => $request->hours_tracked,
                'hours_goal' => $request->hours_goal,
                'hours_remaining' => max(0, $request->hours_goal - $request->hours_tracked),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al enviar recordatorio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reminder status for the current user
     */
    public function status()
    {
        $user = Auth::user();
        $preferences = UserTimePreference::where('user_id', $user->id)->first();

        if (!$preferences) {
            return response()->json([
                'reminders_enabled' => true,
                'daily_goal' => 8,
                'reminder_time' => '17:00:00',
                'should_send_reminder' => false,
                'hours_tracked_today' => 0,
            ]);
        }

        // Calculate today's hours
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();
        
        $hoursToday = TimeEntry::where('user_id', $user->id)
            ->whereBetween('started_at', [$todayStart, $todayEnd])
            ->sum('duration') / 3600;

        // Check if reminder should be sent
        $shouldSendReminder = false;
        if ($preferences->enable_reminders) {
            $reminderTime = Carbon::today()->setTimeFromTimeString($preferences->reminder_time);
            $now = Carbon::now();
            
            $shouldSendReminder = $now->gte($reminderTime) && 
                                  $hoursToday < $preferences->daily_hours_goal;

            // Check if already sent today
            if ($preferences->last_reminder_sent_at) {
                $lastSent = Carbon::parse($preferences->last_reminder_sent_at);
                if ($lastSent->isToday()) {
                    $shouldSendReminder = false;
                }
            }
        }

        return response()->json([
            'reminders_enabled' => $preferences->enable_reminders,
            'daily_goal' => $preferences->daily_hours_goal,
            'reminder_time' => $preferences->reminder_time,
            'should_send_reminder' => $shouldSendReminder,
            'hours_tracked_today' => round($hoursToday, 2),
            'hours_remaining' => max(0, round($preferences->daily_hours_goal - $hoursToday, 2)),
            'last_reminder_sent_at' => $preferences->last_reminder_sent_at,
        ]);
    }

    /**
     * Test reminder notification (for development)
     */
    public function test()
    {
        $user = Auth::user();
        
        try {
            $user->notify(new DailyTimeReminder(5.5, 8));
            
            return response()->json([
                'message' => 'Recordatorio de prueba enviado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al enviar recordatorio de prueba',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}