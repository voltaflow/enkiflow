<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use App\Notifications\InvitationReminderNotification;
use Illuminate\Console\Command;

class SendInvitationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for pending invitations that are about to expire';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending invitation reminders...');
        
        // Find invitations that:
        // - Are pending
        // - Expire in the next 24 hours
        // - Were created more than 3 days ago
        $invitations = Invitation::where('status', 'pending')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDay())
            ->where('created_at', '<=', now()->subDays(3))
            ->with('inviter')
            ->get();
        
        $count = 0;
        
        foreach ($invitations as $invitation) {
            if ($invitation->inviter) {
                // Check if we've already sent a reminder in the last 24 hours
                $recentReminder = $invitation->logs()
                    ->where('action', 'reminder_sent')
                    ->where('created_at', '>', now()->subDay())
                    ->exists();
                
                if (!$recentReminder) {
                    $invitation->inviter->notify(new InvitationReminderNotification($invitation));
                    
                    // Log that we sent a reminder
                    $invitation->logs()->create([
                        'action' => 'reminder_sent',
                        'actor_id' => null,
                        'ip_address' => null,
                        'metadata' => [
                            'type' => 'expiration_reminder',
                            'hours_until_expiration' => $invitation->expires_at->diffInHours(now()),
                        ],
                    ]);
                    
                    $count++;
                }
            }
        }
        
        $this->info("Sent {$count} invitation reminders.");
        
        return Command::SUCCESS;
    }
}