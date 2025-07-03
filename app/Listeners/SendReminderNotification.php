<?php

namespace App\Listeners;

use App\Events\InvitationExpired;
use App\Notifications\InvitationReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendReminderNotification implements ShouldQueue
{
    public function handle(InvitationExpired $event): void
    {
        $invitation = $event->invitation;
        $inviter = $invitation->inviter;
        
        if ($inviter) {
            $inviter->notify(new InvitationReminderNotification($invitation));
        }
    }
}