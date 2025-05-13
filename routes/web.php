<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\SpaceSetupController;
use App\Http\Controllers\SpaceSubscriptionController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Special handling for main domains (enkiflow.test, enkiflow.com, www.enkiflow.com)
$host = request()->getHost();
$mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];

// Cargar landing.php primero para dominios principales
if (in_array($host, $mainDomains)) {
    \Log::info("Loading landing page routes first for domain: {$host}");
    require __DIR__.'/landing.php';
}

if (in_array($host, $mainDomains)) {
    \Log::info("Detected main domain: {$host} - Bypassing tenancy initialization");
    // For main domains, we don't need to initialize tenancy
    // This allows us to show the landing page directly
} else {
    // Handle tenancy initialization for other domains
    if (!function_exists('tenant')) {
        \Log::info("Tenant function not available for host: {$host}");
    } else {
        try {
            // Try to manually initialize tenancy
            $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $host)->first();

            if ($domain) {
                $tenant = \App\Models\Space::find($domain->tenant_id);

                if ($tenant) {
                    \Log::info("Manual tenant initialization for {$host} to tenant {$tenant->id}");
                    tenancy()->initialize($tenant);
                }
            }
        } catch (\Exception $e) {
            \Log::error("Failed to manually initialize tenancy: " . $e->getMessage());
        }
    }
}

// Direct landing page route only for non-main domains (the main domains use landing.php routes)
Route::get('/', function () {
    $host = request()->getHost();
    $mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];

    // For non-main domains, check authentication
    if (auth()->check()) {
        if (auth()->user()->spaces()->count() > 0) {
            return redirect()->route('spaces.index');
        }

        return redirect()->route('spaces.create');
    }

    // Default to showing the landing page for non-main domains
    return view('landing.pages.home', [
        'appearance' => session('appearance', 'system')
    ]);
})->name('home');

// Locale switcher route from landing.php takes precedence for both main and subdomains
Route::get('/set-locale/{locale}', [App\Http\Controllers\LandingController::class, 'setLocale'])->name('set-locale');

Route::get('/dashboard', function () {
    // Get task statistics
    $pendingTasks = \App\Models\Task::where('status', 'pending')->count();
    $inProgressTasks = \App\Models\Task::where('status', 'in_progress')->count();
    $completedTasks = \App\Models\Task::where('status', 'completed')->count();
    $totalTasks = $pendingTasks + $inProgressTasks + $completedTasks;
    
    // Get project statistics
    $pendingProjects = \App\Models\Project::where('status', 'active')->count();
    $completedProjects = \App\Models\Project::where('status', 'completed')->count();
    $totalProjects = $pendingProjects + $completedProjects;
    
    // Get recent tasks
    $recentTasks = \App\Models\Task::with(['project'])
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get()
        ->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'due_date' => $task->due_date,
                'priority' => $task->priority,
            ];
        });
    
    // Get overdue tasks
    $overdueTasks = \App\Models\Task::with(['project'])
        ->where('status', '!=', 'completed')
        ->where('due_date', '<', now())
        ->get()
        ->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'due_date' => $task->due_date,
                'project_id' => $task->project_id,
                'project_name' => $task->project->name,
            ];
        });
    
    return Inertia::render('dashboard', [
        'stats' => [
            'pending_tasks' => $pendingTasks,
            'in_progress_tasks' => $inProgressTasks,
            'completed_tasks' => $completedTasks,
            'total_tasks' => $totalTasks,
            'pending_projects' => $pendingProjects,
            'completed_projects' => $completedProjects,
            'total_projects' => $totalProjects,
            'recent_tasks' => $recentTasks,
            'overdue_tasks' => $overdueTasks,
        ]
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Stripe Webhooks - exempt from CSRF protection
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

Route::middleware('auth')->group(function () {
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Space Routes
    Route::resource('spaces', SpaceController::class);
    Route::post('/spaces/{id}/invite', [SpaceController::class, 'invite'])->name('spaces.invite');
    Route::delete('/spaces/{spaceId}/users/{userId}', [SpaceController::class, 'removeUser'])->name('spaces.users.destroy');
    
    // Space Setup Wizard Routes
    Route::prefix('spaces/setup')->name('spaces.setup.')->group(function () {
        Route::get('/', [SpaceSetupController::class, 'index'])->name('index');
        Route::get('/details', [SpaceSetupController::class, 'details'])->name('details');
        Route::post('/invite-members', [SpaceSetupController::class, 'inviteMembers'])->name('invite-members');
        Route::post('/confirm', [SpaceSetupController::class, 'confirm'])->name('confirm');
        Route::post('/store', [SpaceSetupController::class, 'store'])->name('store');
    });

    // Space Subscription Routes
    Route::get('/spaces/{spaceId}/subscriptions/create', [SpaceSubscriptionController::class, 'create'])->name('spaces.subscriptions.create');
    Route::post('/spaces/{spaceId}/subscriptions', [SpaceSubscriptionController::class, 'store'])->name('spaces.subscriptions.store');
    Route::get('/spaces/{spaceId}/subscriptions', [SpaceSubscriptionController::class, 'show'])->name('spaces.subscriptions.show');
    Route::patch('/spaces/{spaceId}/subscriptions', [SpaceSubscriptionController::class, 'update'])->name('spaces.subscriptions.update');
    Route::delete('/spaces/{spaceId}/subscriptions', [SpaceSubscriptionController::class, 'destroy'])->name('spaces.subscriptions.destroy');
    Route::post('/spaces/{spaceId}/subscriptions/resume', [SpaceSubscriptionController::class, 'resume'])->name('spaces.subscriptions.resume');
    Route::patch('/spaces/{spaceId}/subscriptions/payment-method', [SpaceSubscriptionController::class, 'updatePaymentMethod'])->name('spaces.subscriptions.payment-method.update');
    Route::get('/spaces/{spaceId}/billing-portal', [SpaceSubscriptionController::class, 'billingPortal'])->name('spaces.billing-portal');
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
require __DIR__.'/tenant.php';

// Las rutas de landing ya se cargaron al inicio del archivo
// para los dominios principales.
