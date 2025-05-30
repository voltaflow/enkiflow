<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyTimesheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_start_date',
        'status',
        'total_hours',
        'total_billable_hours',
        'total_amount',
        'submitted_at',
        'approved_at',
        'approved_by',
        'approval_notes',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_hours' => 'decimal:2',
        'total_billable_hours' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the timesheet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved the timesheet.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the time entries for this timesheet.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Scope a query to only include timesheets for a specific week.
     */
    public function scopeForWeek($query, $weekStart)
    {
        return $query->where('week_start_date', Carbon::parse($weekStart)->startOfWeek());
    }

    /**
     * Scope a query to only include draft timesheets.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include submitted timesheets.
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope a query to only include approved timesheets.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Calculate totals from time entries.
     */
    public function calculateTotals()
    {
        $entries = $this->timeEntries;

        $this->total_hours = $entries->sum('duration') / 3600; // Convert seconds to hours
        $this->total_billable_hours = $entries->where('is_billable', true)->sum('duration') / 3600;
        $this->total_amount = $entries->where('is_billable', true)->sum(function ($entry) {
            return ($entry->duration / 3600) * ($entry->hourly_rate ?? 0);
        });

        return $this;
    }

    /**
     * Submit the timesheet for approval.
     */
    public function submit()
    {
        $this->status = 'submitted';
        $this->submitted_at = now();
        $this->save();

        // Lock all related time entries
        $this->timeEntries()->update([
            'locked' => true,
            'locked_at' => now(),
            'locked_by' => auth()->id(),
        ]);

        return $this;
    }

    /**
     * Approve the timesheet.
     */
    public function approve($notes = null)
    {
        $this->status = 'approved';
        $this->approved_at = now();
        $this->approved_by = auth()->id();
        $this->approval_notes = $notes;
        $this->save();

        return $this;
    }

    /**
     * Reject the timesheet.
     */
    public function reject($notes = null)
    {
        $this->status = 'rejected';
        $this->approval_notes = $notes;
        $this->save();

        // Unlock all related time entries
        $this->timeEntries()->update([
            'locked' => false,
            'locked_at' => null,
            'locked_by' => null,
        ]);

        return $this;
    }

    /**
     * Get or create a timesheet for the given week.
     */
    public static function findOrCreateForWeek($userId, $weekStart)
    {
        $weekStartDate = Carbon::parse($weekStart)->startOfWeek();

        return static::firstOrCreate([
            'user_id' => $userId,
            'week_start_date' => $weekStartDate,
        ], [
            'status' => 'draft',
            'total_hours' => 0,
            'total_billable_hours' => 0,
            'total_amount' => 0,
        ]);
    }

    /**
     * Get the week end date.
     */
    public function getWeekEndDateAttribute()
    {
        return $this->week_start_date->copy()->endOfWeek();
    }

    /**
     * Check if the timesheet is editable.
     */
    public function getIsEditableAttribute()
    {
        return in_array($this->status, ['draft', 'rejected']);
    }
}
