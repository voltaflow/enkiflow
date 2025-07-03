<?php

namespace App\Notifications;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invitation;
    protected $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invitation $invitation, User $user)
    {
        $this->invitation = $invitation;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $spaceName = $this->invitation->space->name;
        
        return (new MailMessage)
            ->subject("Invitación aceptada - {$spaceName}")
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("{$this->user->name} ha aceptado tu invitación para unirse a {$spaceName}.")
            ->line("Email: {$this->user->email}")
            ->line("Rol asignado: {$this->invitation->role}")
            ->action('Ver Miembros del Espacio', url("/users"))
            ->line('Gracias por hacer crecer tu equipo.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'space_id' => $this->invitation->tenant_id,
            'space_name' => $this->invitation->space->name,
        ];
    }
}