<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class TeamInvitation extends Notification
{
    use Queueable;

    protected $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $space = $this->invitation->space;
        $inviter = $this->invitation->inviter;
        $inviterName = $inviter ? $inviter->name : 'El administrador';
        $role = \App\Enums\SpaceRole::tryFrom($this->invitation->role)?->label() ?? $this->invitation->role;
        
        // Opcionalmente usar URL firmada para mayor seguridad
        $url = URL::signedRoute('invitation.show', ['token' => $this->invitation->token]);

        return (new MailMessage)
            ->subject("Invitaci�n a unirse a {$space->name} en Enkiflow")
            ->markdown('emails.invitations.team-invitation', [
                'invitation' => $this->invitation,
                'space' => $space,
                'inviterName' => $inviterName,
                'role' => $role,
                'acceptUrl' => $url,
                'expiresAt' => $this->invitation->expires_at,
            ]);
    }
}