<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\UserTimePreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TimePreferenceController extends Controller
{
    /**
     * Get the time preferences for the authenticated user
     */
    public function show()
    {
        $userId = Auth::id();
        
        $preferences = UserTimePreference::firstOrCreate(
            ['user_id' => $userId],
            $this->defaultPreferences()
        );

        return response()->json([
            'preferences' => $preferences->only([
                'daily_hours_goal',
                'reminder_time',
                'enable_idle_detection',
                'enable_reminders',
                'idle_threshold_minutes',
                'allow_multiple_timers',
                'default_billable',
                'week_starts_on',
                'show_weekend_days',
                'time_format',
                'date_format',
                'email_daily_summary',
                'email_weekly_summary',
                'push_notifications',
            ])
        ]);
    }

    /**
     * Update the time preferences for the authenticated user
     */
    public function update(Request $request)
    {
        $request->validate([
            'daily_hours_goal' => 'nullable|numeric|min:0|max:24',
            'reminder_time' => 'nullable|date_format:H:i',
            'enable_idle_detection' => 'nullable|boolean',
            'enable_reminders' => 'nullable|boolean',
            'idle_threshold_minutes' => 'nullable|integer|min:1|max:60',
            'allow_multiple_timers' => 'nullable|boolean',
            'default_billable' => ['nullable', Rule::in(['project', 'yes', 'no'])],
            'week_starts_on' => ['nullable', Rule::in(['monday', 'sunday'])],
            'show_weekend_days' => 'nullable|boolean',
            'time_format' => ['nullable', Rule::in(['24h', '12h'])],
            'date_format' => ['nullable', Rule::in(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'])],
            'email_daily_summary' => 'nullable|boolean',
            'email_weekly_summary' => 'nullable|boolean',
            'push_notifications' => 'nullable|boolean',
        ]);

        $userId = Auth::id();
        
        $preferences = UserTimePreference::updateOrCreate(
            ['user_id' => $userId],
            array_merge(
                $this->defaultPreferences(),
                $request->only([
                    'daily_hours_goal',
                    'reminder_time',
                    'enable_idle_detection',
                    'enable_reminders',
                    'idle_threshold_minutes',
                    'allow_multiple_timers',
                    'default_billable',
                    'week_starts_on',
                    'show_weekend_days',
                    'time_format',
                    'date_format',
                    'email_daily_summary',
                    'email_weekly_summary',
                    'push_notifications',
                ])
            )
        );

        return response()->json([
            'message' => 'Preferencias actualizadas exitosamente',
            'preferences' => $preferences->only([
                'daily_hours_goal',
                'reminder_time',
                'enable_idle_detection',
                'enable_reminders',
                'idle_threshold_minutes',
                'allow_multiple_timers',
                'default_billable',
                'week_starts_on',
                'show_weekend_days',
                'time_format',
                'date_format',
                'email_daily_summary',
                'email_weekly_summary',
                'push_notifications',
            ])
        ]);
    }

    /**
     * Reset preferences to default values
     */
    public function reset()
    {
        $userId = Auth::id();
        
        $preferences = UserTimePreference::updateOrCreate(
            ['user_id' => $userId],
            $this->defaultPreferences()
        );

        return response()->json([
            'message' => 'Preferencias restablecidas a valores por defecto',
            'preferences' => $preferences->only([
                'daily_hours_goal',
                'reminder_time',
                'enable_idle_detection',
                'enable_reminders',
                'idle_threshold_minutes',
                'allow_multiple_timers',
                'default_billable',
                'week_starts_on',
                'show_weekend_days',
                'time_format',
                'date_format',
                'email_daily_summary',
                'email_weekly_summary',
                'push_notifications',
            ])
        ]);
    }

    /**
     * Get default preferences
     */
    private function defaultPreferences(): array
    {
        return [
            'daily_hours_goal' => 8.00,
            'reminder_time' => '17:00',
            'enable_idle_detection' => true,
            'enable_reminders' => true,
            'idle_threshold_minutes' => 10,
            'allow_multiple_timers' => false,
            'default_billable' => 'project',
            'week_starts_on' => 'monday',
            'show_weekend_days' => true,
            'time_format' => '24h',
            'date_format' => 'DD/MM/YYYY',
            'email_daily_summary' => false,
            'email_weekly_summary' => true,
            'push_notifications' => true,
        ];
    }
}