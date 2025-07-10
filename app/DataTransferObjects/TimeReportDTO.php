<?php

namespace App\DataTransferObjects;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class TimeReportDTO extends Data
{
    public function __construct(
        public Carbon $startDate,
        public Carbon $endDate,
        public Collection $entries,
        public ?Project $project = null,
        public ?User $user = null,
        public array $filters = [],
        public bool $isBillingReport = false,
        public array $kpis = []
    ) {
    }
    
    /**
     * Get total duration across all entries
     */
    public function getTotalDuration(): float
    {
        return $this->entries->sum(function ($entry) {
            return $entry->total_duration ?? $entry->duration ?? 0;
        });
    }
    
    /**
     * Get total billable duration
     */
    public function getTotalBillableDuration(): float
    {
        return $this->entries->sum(function ($entry) {
            if (isset($entry->billable_duration)) {
                return $entry->billable_duration;
            }
            return ($entry->is_billable ?? false) ? ($entry->duration ?? 0) : 0;
        });
    }
    
    /**
     * Get total billable amount (for billing reports)
     */
    public function getTotalBillableAmount(): float
    {
        return $this->entries->sum(function ($entry) {
            if (isset($entry->total_amount)) {
                return $entry->total_amount;
            }
            // Calculate based on billable hours and default rate
            $hourlyRate = 50; // Default $50/hour
            if ($entry->is_billable ?? false) {
                return ($entry->duration ?? 0) / 3600 * $hourlyRate;
            }
            return 0;
        });
    }
    
    /**
     * Group entries by date
     */
    public function groupByDate(): Collection
    {
        return $this->entries->groupBy('entry_date');
    }
    
    /**
     * Group entries by project
     */
    public function groupByProject(): Collection
    {
        return $this->entries->groupBy(function ($entry) {
            return $entry->project ? $entry->project->name : 'No Project';
        });
    }
    
    /**
     * Group entries by user
     */
    public function groupByUser(): Collection
    {
        return $this->entries->groupBy(function ($entry) {
            return $entry->user ? $entry->user->name : 'Unknown User';
        });
    }
    
    /**
     * Convert to array format suitable for API response
     */
    public function toApiResponse(): array
    {
        return [
            'meta' => [
                'start_date' => $this->startDate->toDateString(),
                'end_date' => $this->endDate->toDateString(),
                'total_duration' => $this->getTotalDuration(),
                'total_billable_duration' => $this->getTotalBillableDuration(),
                'total_billable_amount' => $this->getTotalBillableAmount(),
                'filters' => $this->filters,
                'kpis' => $this->kpis,
            ],
            'project' => $this->project ? [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ] : null,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null,
            'entries' => $this->entries->map(function ($entry) {
                // Calculate amount based on duration and a default rate
                $hourlyRate = 50; // Default $50/hour, should come from project or user settings
                $amount = $entry->is_billable ? ($entry->duration / 3600) * $hourlyRate : 0;
                
                return [
                    'date' => $entry->started_at ?? $entry->entry_date ?? $entry->date,
                    'project' => $entry->project ? [
                        'id' => $entry->project->id,
                        'name' => $entry->project->name,
                    ] : null,
                    'user' => $entry->user ? [
                        'id' => $entry->user->id,
                        'name' => $entry->user->name,
                    ] : null,
                    'duration' => $entry->total_duration ?? $entry->duration,
                    'billable_duration' => $entry->billable_duration ?? ($entry->is_billable ? $entry->duration : 0),
                    'amount' => $entry->total_amount ?? $amount,
                    'day_of_week' => $entry->day_of_week ?? null,
                    'client_id' => $entry->client_id ?? ($entry->project?->client_id ?? null),
                ];
            })->values()->toArray(),
        ];
    }
}