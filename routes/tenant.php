<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\ProjectController;
use App\Http\Controllers\Tenant\TaskController;
use App\Http\Controllers\Tenant\TimeEntryController;
use App\Http\Controllers\Tenant\TimerController;
use App\Http\Controllers\Tenant\ReportController;
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
    \App\Http\Middleware\CustomDomainTenancyInitializer::class, // Use our custom initializer
    \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
    // NO incluir EnsureValidTenant aquí - se incluirá solo en rutas autenticadas
])->group(function () {
    // Ruta raíz para subdominios de tenant
    Route::get('/', function () {
        return redirect()->route('tenant.dashboard');
    })->name('tenant.root');

    // Require authentication and tenant access for tenant routes
    Route::middleware(['auth', 'tenant.access'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Tenant\DashboardController::class, 'index'])->name('tenant.dashboard');

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

        // Time tracking
        Route::prefix('time')->name('tenant.time.')->group(function () {
            // Time tracking main views
            Route::get('/', [TimeEntryController::class, 'index'])->name('index');
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
        });

        // Timer API endpoints
        Route::prefix('api/timer')->name('api.timer.')->group(function () {
            Route::get('/current', [TimerController::class, 'current'])->name('current');
            Route::post('/start', [TimerController::class, 'start'])->name('start');
            Route::post('/{timer}/stop', [TimerController::class, 'stop'])->name('stop');
            Route::post('/{timer}/pause', [TimerController::class, 'pause'])->name('pause');
            Route::post('/{timer}/resume', [TimerController::class, 'resume'])->name('resume');
            Route::put('/{timer}', [TimerController::class, 'update'])->name('update');
            Route::delete('/{timer}', [TimerController::class, 'destroy'])->name('destroy');
        });

        // Report API endpoints
        Route::prefix('api/reports')->name('api.reports.')->group(function () {
            Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
            Route::get('/weekly', [ReportController::class, 'weekly'])->name('weekly');
            Route::get('/monthly', [ReportController::class, 'monthly'])->name('monthly');
            Route::get('/project', [ReportController::class, 'project'])->name('project');
        });

        // User roles and permissions
        Route::get('/users', [App\Http\Controllers\Tenant\UserRoleController::class, 'index'])->name('tenant.users.index');
        Route::get('/users/invite', [App\Http\Controllers\Tenant\UserRoleController::class, 'create'])->name('tenant.users.invite');
        Route::post('/users/invite', [App\Http\Controllers\Tenant\UserRoleController::class, 'store'])->name('tenant.users.store');
        Route::put('/users/{user}/role', [App\Http\Controllers\Tenant\UserRoleController::class, 'update'])->name('tenant.users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\Tenant\UserRoleController::class, 'destroy'])->name('tenant.users.destroy');
    });
});
