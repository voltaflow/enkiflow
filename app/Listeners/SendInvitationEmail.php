<?php

namespace App\Listeners;

use App\Events\InvitationSent;
use App\Notifications\TeamInvitation;

class SendInvitationEmail
{
    /**
     * Handle the event.
     */
    public function handle(InvitationSent $event): void
    {
        $invitation = $event->invitation;
        
        // Create a temporary notification target
        $notifiable = new \Illuminate\Notifications\AnonymousNotifiable;
        $notifiable->route('mail', $invitation->email);
        
        // Send the notification immediately (bypass queue)
        $notifiable->notifyNow(new TeamInvitation($invitation));
    }
}