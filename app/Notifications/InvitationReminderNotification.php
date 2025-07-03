<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationReminderNotification extends Notification implements ShouldQueue
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
        $daysAgo = $this->invitation->created_at->diffInDays(now());
        
        return (new MailMessage)
            ->subject("Recordatorio: Invitación pendiente - {$spaceName}")
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("Hace {$daysAgo} días enviaste una invitación a {$this->invitation->email} para unirse a {$spaceName}.")
            ->line("La invitación aún no ha sido aceptada y expirará pronto.")
            ->action('Ver Invitaciones', url("/invitations"))
            ->line('Puedes reenviar la invitación o crear una nueva desde el panel de invitaciones.')
            ->line('Si ya no deseas que esta persona se una a tu espacio, puedes revocar la invitación.');
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
            'email' => $this->invitation->email,
            'space_id' => $this->invitation->tenant_id,
            'space_name' => $this->invitation->space->name,
            'days_pending' => $this->invitation->created_at->diffInDays(now()),
        ];
    }
}