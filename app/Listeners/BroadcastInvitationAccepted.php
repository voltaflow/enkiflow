<?php

namespace App\Listeners;

use App\Events\InvitationAccepted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Broadcast;

class BroadcastInvitationAccepted implements ShouldQueue
{
    public function handle(InvitationAccepted $event): void
    {
        $invitation = $event->invitation;
        $space = $invitation->space;
        
        // Broadcast to the space channel
        Broadcast::channel("space.{$space->id}", function ($user) use ($space) {
            return $user->spaces()->where('tenant_id', $space->id)->exists();
        });
        
        // Send the broadcast message
        broadcast(new \App\Events\MemberJoinedSpace($space, $event->user))
            ->toOthers();
    }
}