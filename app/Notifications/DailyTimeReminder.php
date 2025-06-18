<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyTimeReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $hoursTracked;
    protected $hoursGoal;

    /**
     * Create a new notification instance.
     */
    public function __construct(float $hoursTracked, float $hoursGoal)
    {
        $this->hoursTracked = $hoursTracked;
        $this->hoursGoal = $hoursGoal;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Add mail channel if user has email notifications enabled
        if ($notifiable->userTimePreference && $notifiable->userTimePreference->email_daily_summary) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $hoursRemaining = max(0, $this->hoursGoal - $this->hoursTracked);
        
        return (new MailMessage)
            ->subject('Recordatorio de Registro de Tiempo - EnkiFlow')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Este es tu recordatorio diario para completar tu registro de tiempo.')
            ->line('**Resumen del día:**')
            ->line('- Horas registradas: ' . number_format($this->hoursTracked, 1) . ' horas')
            ->line('- Meta diaria: ' . number_format($this->hoursGoal, 1) . ' horas')
            ->line('- Horas restantes: ' . number_format($hoursRemaining, 1) . ' horas')
            ->action('Registrar Tiempo', url('/time'))
            ->line('Recuerda registrar tu tiempo antes de finalizar tu jornada laboral.')
            ->salutation('Saludos, El equipo de EnkiFlow');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'daily_time_reminder',
            'hours_tracked' => $this->hoursTracked,
            'hours_goal' => $this->hoursGoal,
            'hours_remaining' => max(0, $this->hoursGoal - $this->hoursTracked),
            'message' => sprintf(
                'Has registrado %.1f de %.1f horas hoy. Te faltan %.1f horas para alcanzar tu meta diaria.',
                $this->hoursTracked,
                $this->hoursGoal,
                max(0, $this->hoursGoal - $this->hoursTracked)
            ),
        ];
    }
}