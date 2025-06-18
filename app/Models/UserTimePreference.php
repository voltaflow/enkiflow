<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTimePreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
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
        'last_reminder_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'daily_hours_goal' => 'decimal:2',
        'enable_idle_detection' => 'boolean',
        'enable_reminders' => 'boolean',
        'idle_threshold_minutes' => 'integer',
        'allow_multiple_timers' => 'boolean',
        'show_weekend_days' => 'boolean',
        'email_daily_summary' => 'boolean',
        'email_weekly_summary' => 'boolean',
        'push_notifications' => 'boolean',
        'last_reminder_sent_at' => 'datetime',
    ];

    /**
     * Get the user that owns the preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reminder time as a Carbon instance.
     */
    public function getReminderTimeAttribute($value)
    {
        return $value ? \Carbon\Carbon::createFromTimeString($value) : null;
    }

    /**
     * Check if reminders are enabled and due.
     */
    public function shouldSendReminder(): bool
    {
        if (!$this->enable_reminders) {
            return false;
        }

        // If reminder was already sent today, don't send again
        if ($this->last_reminder_sent_at && $this->last_reminder_sent_at->isToday()) {
            return false;
        }

        // Check if current time is past reminder time
        $now = now();
        $reminderTime = $this->reminder_time;
        
        if ($reminderTime && $now->format('H:i') >= $reminderTime->format('H:i')) {
            return true;
        }

        return false;
    }
}