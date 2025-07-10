<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $jobId,
        protected string $reportType
    ) {
    }
    
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Report is Ready')
            ->line('Your requested report has been generated and is ready for download.')
            ->action('Download Report', route('reports.download', ['jobId' => $this->jobId]))
            ->line('The report will be available for the next 7 days.');
    }
    
    public function toArray($notifiable): array
    {
        return [
            'job_id' => $this->jobId,
            'report_type' => $this->reportType,
            'message' => 'Your requested report has been generated and is ready for download.',
            'download_url' => route('reports.download', ['jobId' => $this->jobId]),
        ];
    }
}