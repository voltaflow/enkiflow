<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySummary extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'total_time_seconds',
        'manual_time',
        'tracked_time',
        'most_used_app',
        'productivity_summary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'total_time_seconds' => 'integer',
        'manual_time' => 'integer',
        'tracked_time' => 'integer',
        'productivity_summary' => 'array',
    ];

    /**
     * Get the user that owns the daily summary.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate or update the daily summary for a user and date.
     */
    public static function generateForUserAndDate(int $userId, string $date): self
    {
        $summary = self::firstOrNew([
            'user_id' => $userId,
            'date' => $date,
        ]);

        // Calculate manual time from time entries
        $manualTime = TimeEntry::where('user_id', $userId)
            ->whereDate('started_at', $date)
            ->sum('duration');

        // Calculate tracked time from application sessions
        $trackedTime = ApplicationSession::where('user_id', $userId)
            ->whereDate('start_time', $date)
            ->sum('duration_seconds');

        // Get most used app
        $mostUsedApp = ApplicationSession::where('user_id', $userId)
            ->whereDate('start_time', $date)
            ->select('app_name')
            ->selectRaw('SUM(duration_seconds) as total_duration')
            ->groupBy('app_name')
            ->orderByDesc('total_duration')
            ->first();

        // Calculate productivity summary
        $productivitySummary = self::calculateProductivitySummary($userId, $date);

        $summary->fill([
            'manual_time' => $manualTime,
            'tracked_time' => $trackedTime,
            'total_time_seconds' => $manualTime + $trackedTime,
            'most_used_app' => $mostUsedApp?->app_name,
            'productivity_summary' => $productivitySummary,
        ]);

        $summary->save();

        return $summary;
    }

    /**
     * Calculate productivity summary for a user and date.
     */
    protected static function calculateProductivitySummary(int $userId, string $date): array
    {
        $sessions = ApplicationSession::where('user_id', $userId)
            ->whereDate('start_time', $date)
            ->with('category')
            ->get();

        $productive = 0;
        $neutral = 0;
        $distracting = 0;

        foreach ($sessions as $session) {
            switch ($session->productivity_level) {
                case 'productive':
                    $productive += $session->duration_seconds;
                    break;
                case 'distracting':
                    $distracting += $session->duration_seconds;
                    break;
                default:
                    $neutral += $session->duration_seconds;
                    break;
            }
        }

        $total = $productive + $neutral + $distracting;

        return [
            'productive_seconds' => $productive,
            'neutral_seconds' => $neutral,
            'distracting_seconds' => $distracting,
            'productive_percentage' => $total > 0 ? round(($productive / $total) * 100, 2) : 0,
            'neutral_percentage' => $total > 0 ? round(($neutral / $total) * 100, 2) : 0,
            'distracting_percentage' => $total > 0 ? round(($distracting / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Scope a query to only include summaries for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include summaries for a specific date range.
     */
    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Get formatted total time.
     */
    public function getFormattedTotalTimeAttribute(): string
    {
        $hours = floor($this->total_time_seconds / 3600);
        $minutes = floor(($this->total_time_seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
