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