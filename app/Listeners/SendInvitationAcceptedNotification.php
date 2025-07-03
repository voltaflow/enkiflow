<?php

namespace App\Listeners;

use App\Events\InvitationAccepted;
use App\Notifications\InvitationAcceptedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvitationAcceptedNotification implements ShouldQueue
{
    public function handle(InvitationAccepted $event): void
    {
        $invitation = $event->invitation;
        $inviter = $invitation->inviter;
        
        if ($inviter) {
            $inviter->notify(new InvitationAcceptedNotification($invitation, $event->user));
        }
    }
}