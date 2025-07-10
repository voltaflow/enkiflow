<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportDataUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tenantId;
    public $reportType;
    public $metrics;

    public function __construct(string $tenantId, string $reportType, array $metrics)
    {
        $this->tenantId = $tenantId;
        $this->reportType = $reportType;
        $this->metrics = $metrics;
    }

    public function broadcastOn()
    {
        return new Channel("tenant.{$this->tenantId}.reports");
    }
    
    public function broadcastAs()
    {
        return 'report.updated';
    }
}