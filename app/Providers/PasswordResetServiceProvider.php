<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class PasswordResetServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Personalizar la notificación de reset de contraseña
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            // Crear una clave única para este reset
            $cacheKey = 'password_reset_sent:' . $notifiable->email . ':' . substr($token, 0, 10);
            
            // Verificar si ya se envió este correo en los últimos 2 minutos
            if (Cache::has($cacheKey)) {
                \Log::warning('Duplicate password reset email prevented', [
                    'email' => $notifiable->email,
                    'token_prefix' => substr($token, 0, 10)
                ]);
                return null; // No enviar el correo
            }
            
            // Marcar como enviado
            Cache::put($cacheKey, true, now()->addMinutes(2));
            
            // Construir el correo normalmente
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->email,
            ], false));
            
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Restablecer Contraseña')
                ->line('Estás recibiendo este correo porque hemos recibido una solicitud de restablecimiento de contraseña para tu cuenta.')
                ->action('Restablecer Contraseña', $url)
                ->line('Este enlace de restablecimiento de contraseña expirará en :count minutos.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')])
                ->line('Si no solicitaste un restablecimiento de contraseña, no se requiere ninguna acción adicional.');
        });
    }
}