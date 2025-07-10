<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KpiAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
        protected string $kpiType,
        protected float $currentValue
    ) {
    }
    
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->message);
            
        // Add action based on KPI type
        switch ($this->kpiType) {
            case 'billable_utilization':
                $mail->action('View Billing Report', url('/reports/billing'));
                break;
            case 'budget_burn_rate':
                $mail->action('View Project Reports', url('/reports/projects'));
                break;
            case 'overtime_hours':
                $mail->action('View Time Entries', url('/time-entries'));
                break;
            case 'capacity_utilization':
                $mail->action('View Dashboard', url('/dashboard'));
                break;
        }
        
        $mail->line('Please review this alert and take appropriate action if necessary.');
        
        return $mail;
    }
    
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'kpi_type' => $this->kpiType,
            'current_value' => $this->currentValue,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}