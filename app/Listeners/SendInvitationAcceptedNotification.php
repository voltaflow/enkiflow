<?php

namespace App\Listeners;

use App\Events\InvitationAccepted;
use App\Notifications\InvitationAcceptedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

class SendInvitationAcceptedNotification implements ShouldQueue
{
    public function handle(InvitationAccepted $event): void
    {
        $invitation = $event->invitation;
        $inviter = $invitation->inviter;
        
        if (!$inviter) {
            return;
        }
        
        // Crear una clave única para este evento
        $cacheKey = 'invitation_accepted_email:' . $invitation->id . ':' . $event->user->id;
        
        // Verificar si ya se envió esta notificación en los últimos 5 minutos
        if (Cache::has($cacheKey)) {
            \Log::warning('Duplicate invitation accepted email prevented', [
                'invitation_id' => $invitation->id,
                'user_id' => $event->user->id
            ]);
            return;
        }
        
        // Marcar como enviado antes de enviar
        Cache::put($cacheKey, true, now()->addMinutes(5));
        
        $inviter->notify(new InvitationAcceptedNotification($invitation, $event->user));
    }
}