<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ===================================
        // BACKUPS AUTOMÁTICOS
        // ===================================
        
        // Backup diario de todos los tenants activos (a las 2:00 AM)
        // Mantener los 7 backups más recientes por tenant
        $schedule->command('tenants:backup --all --keep=7')
                 ->dailyAt('02:00')
                 ->name('daily-tenant-backups')
                 ->onOneServer()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/tenant-backups.log'))
                 ->onSuccess(function () {
                     \Log::info('Backup diario de tenants completado exitosamente');
                 })
                 ->onFailure(function () {
                     \Log::error('Error en el backup diario de tenants');
                     // Aquí podrías enviar una notificación al administrador
                 });

        // ===================================
        // MANTENIMIENTO REGULAR
        // ===================================
        
        // Mantenimiento semanal (domingo a las 4:00 AM)
        // Optimizar bases de datos y limpiar datos antiguos
        $schedule->command('tenants:maintenance --all --optimize --cleanup --analyze')
                 ->weeklyOn(0, '04:00') // 0 = Domingo
                 ->name('weekly-tenant-maintenance')
                 ->onOneServer()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/tenant-maintenance.log'))
                 ->emailOutputOnFailure(config('tenancy.admin_email'));
        
        // Mantenimiento mensual más intensivo (primer día del mes a las 3:00 AM)
        // Incluye VACUUM para recuperar espacio en disco
        $schedule->command('tenants:maintenance --all --vacuum --analyze')
                 ->monthlyOn(1, '03:00')
                 ->name('monthly-tenant-vacuum')
                 ->onOneServer()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/tenant-vacuum.log'));

        // ===================================
        // LIMPIEZA DE DATOS
        // ===================================
        
        // Limpieza diaria de datos muy antiguos (más de 365 días) a las 5:00 AM
        $schedule->command('tenants:maintenance --all --cleanup --days=365')
                 ->dailyAt('05:00')
                 ->name('daily-old-data-cleanup')
                 ->onOneServer()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/tenant-cleanup.log'));

        // ===================================
        // AGREGACIÓN DE ACTIVIDADES
        // ===================================
        
        // Agregar datos de actividad cada 6 horas para optimizar reportes
        $schedule->command('time-entries:aggregate-activities --days=1')
                 ->everySixHours()
                 ->name('aggregate-time-entry-activities')
                 ->onOneServer()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/activity-aggregation.log'));

        // ===================================
        // MONITOREO Y SALUD
        // ===================================
        
        // Verificar el estado de las migraciones de tenants cada hora
        $schedule->command('tenants:migration-status')
                 ->hourly()
                 ->name('check-tenant-migrations')
                 ->runInBackground()
                 ->onSuccess(function () {
                     // Solo registrar si hay problemas
                 });

        // ===================================
        // TAREAS DE DESARROLLO (solo en ambiente local)
        // ===================================
        
        if (app()->environment('local')) {
            // Backup de prueba cada 10 minutos (solo para desarrollo)
            $schedule->command('tenants:backup --all --keep=3')
                     ->everyTenMinutes()
                     ->name('dev-frequent-backups')
                     ->withoutOverlapping();
        }

        // ===================================
        // TIMER CLEANUP
        // ===================================
        
        // Clean up idle timers every hour
        $schedule->command('timers:cleanup-idle --minutes=480')
                 ->hourly()
                 ->name('cleanup-idle-timers')
                 ->onOneServer()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/timer-cleanup.log'));

        // ===================================
        // INVITACIONES
        // ===================================
        
        // Ejecutar diariamente para expirar invitaciones antiguas
        $schedule->command('invitations:expire')
                 ->daily()
                 ->name('expire-old-invitations')
                 ->onOneServer()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/invitations.log'));
        
        // Ejecutar semanalmente para purgar invitaciones antiguas (GDPR)
        $schedule->command('invitations:prune')
                 ->weekly()
                 ->name('prune-old-invitations')
                 ->onOneServer()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/invitations.log'));
        
        // Enviar recordatorios de invitaciones próximas a expirar
        $schedule->command('invitations:send-reminders')
                 ->dailyAt('09:00')
                 ->name('send-invitation-reminders')
                 ->onOneServer()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/invitations.log'));

        // ===================================
        // REPORTES Y KPIs
        // ===================================
        
        // Update dashboard KPIs every 15 minutes during business hours
        $schedule->command('reports:update-kpis')
            ->weekdays()
            ->everyFifteenMinutes()
            ->between('8:00', '18:00')
            ->name('update-dashboard-kpis')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/kpis-update.log'));
        
        // Check KPI thresholds and send alerts daily
        $schedule->call(function () {
            $alertService = app(\App\Services\KpiAlertService::class);
            $alertService->checkAndSendAlerts();
        })->dailyAt('09:00')
          ->name('check-kpi-alerts')
          ->onOneServer()
          ->runInBackground();

        // ===================================
        // HORIZON (si está instalado)
        // ===================================
        
        if (class_exists(\Laravel\Horizon\Console\SnapshotCommand::class)) {
            // Capturar métricas de Horizon cada 5 minutos
            $schedule->command('horizon:snapshot')->everyFiveMinutes();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}