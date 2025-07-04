<?php

namespace App\Listeners;

use App\Events\InvitationSent;
use App\Notifications\TeamInvitation;
use Illuminate\Support\Facades\Cache;

class SendInvitationEmail
{
    /**
     * Handle the event.
     */
    public function handle(InvitationSent $event): void
    {
        $invitation = $event->invitation;
        
        // Crear una clave única para este evento
        $cacheKey = 'invitation_email_sent:' . $invitation->id . ':' . md5($invitation->updated_at->toString());
        
        // Verificar si ya se envió este correo en los últimos 5 minutos
        if (Cache::has($cacheKey)) {
            \Log::warning('Duplicate invitation email prevented', [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email
            ]);
            return;
        }
        
        // Marcar como enviado antes de enviar (para evitar race conditions)
        Cache::put($cacheKey, true, now()->addMinutes(5));
        
        // Create a temporary notification target
        $notifiable = new \Illuminate\Notifications\AnonymousNotifiable;
        $notifiable->route('mail', $invitation->email);
        
        // Send the notification immediately (bypass queue)
        $notifiable->notifyNow(new TeamInvitation($invitation));
    }
}