<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\ClientController;
use App\Http\Controllers\Tenant\ProjectController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\TaskController;
use App\Http\Controllers\Tenant\TimeEntryController;
use App\Http\Controllers\Tenant\TimerController;
use App\Http\Controllers\Tenant\WeeklyTimesheetController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    \App\Http\Middleware\ShareSessionAcrossDomains::class, // Primero compartir sesiones
    \App\Http\Middleware\CustomDomainTenancyInitializer::class, // Luego inicializar tenant
    \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
    \App\Http\Middleware\EnsureTenantUrl::class, // Asegurar URLs correctas para tenant
    // NO incluir EnsureValidTenant aquí - se incluirá solo en rutas autenticadas
])->group(function () {
    // Include auth routes for tenant domains
    require __DIR__.'/auth.php';
    // Ruta raíz para subdominios de tenant
    Route::get('/', function () {
        return redirect()->route('tenant.dashboard');
    })->name('tenant.root');
    
    // Nueva ruta de autologin simplificada
    Route::get('/autologin/{token}', function ($token) {
        // Buscar el token en Redis conexión compartida
        $redis = \Illuminate\Support\Facades\Redis::connection('shared');
        $redisKey = 'autologin:' . $token;
        
        $tokenData = $redis->get($redisKey);
        
        if (!$tokenData) {
            abort(404);
        }
        
        $authData = json_decode($tokenData, true);
        
        // Verificar expiración
        if ($authData['expires_at'] < time()) {
            \Illuminate\Support\Facades\Redis::connection('shared')->del('autologin:' . $token);
            abort(404);
        }
        
        // Obtener el usuario
        $user = \App\Models\User::find($authData['user_id']);
        if (!$user) {
            abort(404);
        }
        
        // Obtener el espacio actual desde el dominio
        $currentDomain = request()->getHost();
        $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $currentDomain)->first();
        $currentSpaceId = $domain ? $domain->tenant_id : null;
        
        if ($currentSpaceId !== $authData['space_id']) {
            abort(404);
        }
        
        // Verificar acceso del usuario al espacio
        $hasAccess = $user->spaces()
            ->where('tenant_id', $authData['space_id'])
            ->exists();
            
        if (!$hasAccess) {
            abort(403);
        }
        
        // Autenticar al usuario
        \Illuminate\Support\Facades\Auth::login($user, true);
        
        // Regenerar sesión por seguridad
        request()->session()->regenerate();
        
        // Inicializar el tenant para la sesión actual
        $space = \App\Models\Space::find($authData['space_id']);
        if ($space) {
            tenancy()->initialize($space);
            session(['current_space_id' => $space->id]);
        }
        
        // Eliminar el token usado
        \Illuminate\Support\Facades\Redis::connection('shared')->del('autologin:' . $token);
        
        // Redirigir al dashboard
        return redirect()->route('tenant.dashboard');
    })->name('tenant.autologin');

    // Require authentication and tenant access for tenant routes
    Route::middleware(['auth', 'tenant.access'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Tenant\DashboardController::class, 'index'])->name('tenant.dashboard');

        // Clients
        Route::resource('clients', ClientController::class)->names([
            'index' => 'tenant.clients.index',
            'create' => 'tenant.clients.create',
            'store' => 'tenant.clients.store',
            'show' => 'tenant.clients.show',
            'edit' => 'tenant.clients.edit',
            'update' => 'tenant.clients.update',
            'destroy' => 'tenant.clients.destroy',
        ]);
        
        // Client actions
        Route::post('/clients/{client}/restore', [ClientController::class, 'restore'])->name('tenant.clients.restore');
        Route::post('/clients/{client}/toggle-status', [ClientController::class, 'toggleStatus'])->name('tenant.clients.toggle-status');
        Route::get('/api/clients/select', [ClientController::class, 'select'])->name('api.clients.select');

        // Projects
        Route::resource('projects', ProjectController::class)->names([
            'index' => 'tenant.projects.index',
            'create' => 'tenant.projects.create',
            'store' => 'tenant.projects.store',
            'show' => 'tenant.projects.show',
            'edit' => 'tenant.projects.edit',
            'update' => 'tenant.projects.update',
            'destroy' => 'tenant.projects.destroy',
        ]);

        // Project actions
        Route::post('/projects/{project}/complete', [ProjectController::class, 'complete'])->name('tenant.projects.complete');
        Route::post('/projects/{project}/reactivate', [ProjectController::class, 'reactivate'])->name('tenant.projects.reactivate');

        // Tasks
        // Task views (must come before resource routes)
        Route::get('/tasks/kanban', [TaskController::class, 'kanban'])->name('tasks.kanban');

        Route::resource('tasks', TaskController::class)->names([
            'index' => 'tasks.index',
            'create' => 'tasks.create',
            'store' => 'tasks.store',
            'show' => 'tasks.show',
            'edit' => 'tasks.edit',
            'update' => 'tasks.update',
            'destroy' => 'tasks.destroy',
        ]);

        // Task actions
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::post('/tasks/{task}/in-progress', [TaskController::class, 'inProgress'])->name('tasks.in-progress');
        Route::post('/tasks/{task}/comments', [TaskController::class, 'addComment'])->name('tasks.comments.store');

        // Kanban actions
        Route::post('/tasks/move', [TaskController::class, 'move'])->name('tasks.move');

        // Bulk actions
        Route::post('/tasks/bulk-destroy', [TaskController::class, 'bulkDestroy'])->name('tasks.bulk-destroy');
        Route::post('/tasks/bulk-complete', [TaskController::class, 'bulkComplete'])->name('tasks.bulk-complete');
        Route::post('/tasks/bulk-in-progress', [TaskController::class, 'bulkInProgress'])->name('tasks.bulk-in-progress');

        // Time tracking
        Route::prefix('time')->name('tenant.time.')->group(function () {
            // Time tracking main views
            Route::get('/', [App\Http\Controllers\Tenant\TimeUnifiedController::class, 'index'])->name('index');
            Route::get('/week-data', [App\Http\Controllers\Tenant\TimeUnifiedController::class, 'weekData'])->name('week-data');
            Route::get('/day-entries', [App\Http\Controllers\Tenant\TimeUnifiedController::class, 'dayEntries'])->name('day-entries');
            Route::get('/report', [TimeEntryController::class, 'report'])->name('report');


            // Time entry CRUD
            Route::post('/', [TimeEntryController::class, 'store'])->name('store');
            Route::put('/{timeEntry}', [TimeEntryController::class, 'update'])->name('update');
            Route::delete('/{timeEntry}', [TimeEntryController::class, 'destroy'])->name('destroy');

            // Timer actions
            Route::post('/start', [TimeEntryController::class, 'start'])->name('start');
            Route::post('/{timeEntry}/stop', [TimeEntryController::class, 'stop'])->name('stop');

            // Utility endpoints
            Route::get('/running', [TimeEntryController::class, 'running'])->name('running');
            Route::post('/{timeEntry}/field', [TimeEntryController::class, 'updateField'])->name('update-field');
            Route::get('/report-data', [TimeEntryController::class, 'reportData'])->name('report-data');
            
            // Duplicate day functionality
            Route::post('/duplicate-day', [TimeEntryController::class, 'duplicateDay'])->name('duplicate-day');
            
            // Copy rows from previous week (Harvest-style)
            Route::post('/copy-previous-week-rows', [TimeEntryController::class, 'copyRowsFromPreviousWeek'])->name('copy-previous-week-rows');
            
            // Add project/task row to weekly timesheet
            Route::post('/add-week-row', [TimeEntryController::class, 'addWeekRow'])->name('add-week-row');
            
            // Approval workflow
            Route::post('/submit', [App\Http\Controllers\Tenant\TimesheetController::class, 'submit'])->name('submit');
            Route::post('/approve', [App\Http\Controllers\Tenant\TimesheetController::class, 'approve'])->name('approve');
            Route::post('/reject', [App\Http\Controllers\Tenant\TimesheetController::class, 'reject'])->name('reject');
            Route::get('/approval-status', [App\Http\Controllers\Tenant\TimesheetController::class, 'status'])->name('approval-status');
            
            // User preferences
            Route::get('/preferences', [App\Http\Controllers\Tenant\TimePreferenceController::class, 'show'])->name('preferences');
            Route::put('/preferences', [App\Http\Controllers\Tenant\TimePreferenceController::class, 'update'])->name('preferences.update');
            Route::post('/preferences/reset', [App\Http\Controllers\Tenant\TimePreferenceController::class, 'reset'])->name('preferences.reset');
            
            // Reminders
            Route::post('/reminders/daily', [App\Http\Controllers\Tenant\ReminderController::class, 'sendDaily'])->name('reminders.daily');
            Route::get('/reminders/status', [App\Http\Controllers\Tenant\ReminderController::class, 'status'])->name('reminders.status');
            Route::post('/reminders/test', [App\Http\Controllers\Tenant\ReminderController::class, 'test'])->name('reminders.test');
        });

        // Timer API endpoints
        Route::prefix('api/timer')->name('api.timer.')->group(function () {
            // Active timer endpoints with persistence (MUST be before parameterized routes)
            Route::get('/active', [TimerController::class, 'active'])->name('active');
            Route::post('/active/start', [TimerController::class, 'startActive'])->name('active.start');
            Route::post('/active/stop', [TimerController::class, 'stopActive'])->name('active.stop');
            Route::post('/active/pause', [TimerController::class, 'pauseActive'])->name('active.pause');
            Route::post('/active/resume', [TimerController::class, 'resumeActive'])->name('active.resume');
            Route::post('/active/sync', [TimerController::class, 'sync'])->name('active.sync');
            Route::put('/active', [TimerController::class, 'updateActive'])->name('active.update');
            Route::delete('/active', [TimerController::class, 'discardActive'])->name('active.discard');
            
            // Regular timer endpoints (with parameters)
            Route::get('/current', [TimerController::class, 'current'])->name('current');
            Route::post('/start', [TimerController::class, 'start'])->name('start');
            Route::post('/{timer}/stop', [TimerController::class, 'stop'])->name('stop');
            Route::post('/{timer}/pause', [TimerController::class, 'pause'])->name('pause');
            Route::post('/{timer}/resume', [TimerController::class, 'resume'])->name('resume');
            Route::put('/{timer}', [TimerController::class, 'update'])->name('update');
            Route::delete('/{timer}', [TimerController::class, 'destroy'])->name('destroy');
        });

        // Time Entry Templates API endpoints
        Route::prefix('api/templates')->name('api.templates.')->group(function () {
            Route::get('/', [App\Http\Controllers\Tenant\TimeEntryTemplateController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Tenant\TimeEntryTemplateController::class, 'store'])->name('store');
            Route::put('/{template}', [App\Http\Controllers\Tenant\TimeEntryTemplateController::class, 'update'])->name('update');
            Route::delete('/{template}', [App\Http\Controllers\Tenant\TimeEntryTemplateController::class, 'destroy'])->name('destroy');
            Route::post('/{template}/use', [App\Http\Controllers\Tenant\TimeEntryTemplateController::class, 'createEntry'])->name('use');
            Route::get('/suggestions', [App\Http\Controllers\Tenant\TimeEntryTemplateController::class, 'suggestions'])->name('suggestions');
            Route::post('/bulk-use', [App\Http\Controllers\Tenant\TimeEntryTemplateController::class, 'bulkCreateEntries'])->name('bulk-use');
        });

        // Export API endpoints
        Route::prefix('api/export')->name('api.export.')->group(function () {
            Route::get('/csv', [App\Http\Controllers\Tenant\TimeEntryExportController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [App\Http\Controllers\Tenant\TimeEntryExportController::class, 'exportPdf'])->name('pdf');
            Route::get('/templates', [App\Http\Controllers\Tenant\TimeEntryExportController::class, 'getTemplates'])->name('templates');
        });

        // Report API endpoints
        Route::prefix('api/reports')->name('api.reports.')->group(function () {
            Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
            Route::get('/weekly', [ReportController::class, 'weekly'])->name('weekly');
            Route::get('/monthly', [ReportController::class, 'monthly'])->name('monthly');
            Route::get('/project', [ReportController::class, 'project'])->name('project');
        });

        // Analytics
        Route::get('/analytics', [App\Http\Controllers\Tenant\AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/api/analytics/data', [App\Http\Controllers\Tenant\AnalyticsController::class, 'data'])->name('api.analytics.data');
        Route::post('/api/analytics/export', [App\Http\Controllers\Tenant\AnalyticsController::class, 'export'])->name('api.analytics.export');

        // User roles and permissions
        Route::get('/users', [App\Http\Controllers\Tenant\UserRoleController::class, 'index'])->name('tenant.users.index');
        Route::get('/users/invite', [App\Http\Controllers\Tenant\UserRoleController::class, 'create'])->name('tenant.users.invite');
        Route::post('/users/invite', [App\Http\Controllers\Tenant\UserRoleController::class, 'store'])->name('tenant.users.store');
        Route::put('/users/{user}/role', [App\Http\Controllers\Tenant\UserRoleController::class, 'update'])->name('tenant.users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\Tenant\UserRoleController::class, 'destroy'])->name('tenant.users.destroy');
        
        // Demo data management
        Route::prefix('settings/developer')->name('settings.')->group(function () {
            Route::get('/demo-data', [App\Http\Controllers\DemoDataController::class, 'index'])->name('demo-data');
            Route::post('/demo-data/generate', [App\Http\Controllers\DemoDataController::class, 'generate'])->name('demo-data.generate');
            Route::post('/demo-data/reset', [App\Http\Controllers\DemoDataController::class, 'reset'])->name('demo-data.reset');
            Route::get('/demo-data/snapshot', [App\Http\Controllers\DemoDataController::class, 'snapshot'])->name('demo-data.snapshot');
            Route::post('/demo-data/clone', [App\Http\Controllers\DemoDataController::class, 'clone'])->name('demo-data.clone');
        });
    });
});
