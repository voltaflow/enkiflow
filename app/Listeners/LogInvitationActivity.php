<?php

namespace App\Listeners;

use App\Events\InvitationAccepted;
use App\Events\InvitationExpired;
use App\Events\InvitationRevoked;
use App\Events\InvitationSent;
use App\Events\InvitationViewed;
use App\Models\InvitationLog;

class LogInvitationActivity
{
    public function handle($event): void
    {
        $invitation = $event->invitation;
        $action = $this->determineAction($event);
        $actorId = $this->determineActor($event);
        
        InvitationLog::create([
            'invitation_id' => $invitation->id,
            'actor_id' => $actorId,
            'action' => $action,
            'ip_address' => $event->ipAddress ?? request()->ip(),
            'metadata' => $this->getMetadata($event),
        ]);
    }
    
    private function determineAction($event): string
    {
        return match (true) {
            $event instanceof InvitationSent => 'sent',
            $event instanceof InvitationViewed => 'viewed',
            $event instanceof InvitationAccepted => 'accepted',
            $event instanceof InvitationRevoked => 'revoked',
            $event instanceof InvitationExpired => 'expired',
            default => 'unknown',
        };
    }
    
    private function determineActor($event): ?int
    {
        if ($event instanceof InvitationSent || $event instanceof InvitationRevoked) {
            return $event->invitation->invited_by;
        }
        
        if ($event instanceof InvitationAccepted && isset($event->user)) {
            return $event->user->id;
        }
        
        return null;
    }
    
    private function getMetadata($event): ?array
    {
        if ($event instanceof InvitationAccepted) {
            return [
                'user_created' => $event->userCreated ?? false,
                'user_id' => $event->user->id ?? null,
            ];
        }
        
        return null;
    }
}