<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\ProjectController;
use App\Http\Controllers\Tenant\TaskController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

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
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Dashboard
    Route::get('/', function () {
        return redirect()->route('tenant.dashboard');
    });

    // Require authentication for tenant routes
    Route::middleware(['auth'])->group(function () {
        // Dashboard
        Route::get('/dashboard', function () {
            return inertia('Tenant/Dashboard');
        })->name('tenant.dashboard');

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
    });
});
