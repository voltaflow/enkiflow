<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Settings\ProfileController;
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

// Health check routes (must be before tenant middleware)
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/health', [HealthCheckController::class, 'health'])->name('health');
    Route::get('/health/db', [HealthCheckController::class, 'database'])->name('health.database');
    Route::get('/health/queue', [HealthCheckController::class, 'queue'])->name('health.queue');
    Route::get('/health/full', [HealthCheckController::class, 'full'])->name('health.full');
});

// Test CSRF route
Route::get('/test-csrf', function () {
    return response()->json([
        'token' => csrf_token(),
        'session_id' => session()->getId(),
        'cookies' => request()->cookies->all(),
    ]);
});

// Check if we're on a main domain
$host = request()->getHost();
$mainDomains = ['enkiflow.test', 'enkiflow.com', 'www.enkiflow.com'];
$isMainDomain = in_array($host, $mainDomains);

// Set the bypass_tenancy attribute to ensure middleware knows this is a main domain
if ($isMainDomain) {
    request()->attributes->set('bypass_tenancy', true);
    request()->attributes->set('is_main_domain', true);
}

// Specially prioritized routes for main domains (before any auth middleware)
// These routes will be accessible without authentication
if ($isMainDomain) {
    // Important: These routes are defined directly here, not in landing.php
    // This ensures they have the highest priority
    Route::group(['middleware' => ['web']], function () {
        // Root route for main domains - CRITICAL
        Route::get('/', [\App\Http\Controllers\LandingController::class, 'index'])
            ->name('landing.home.direct');

        // Other high-priority landing routes
        Route::get('/features', [\App\Http\Controllers\LandingController::class, 'features'])
            ->name('landing.features.direct');
        Route::get('/pricing', [\App\Http\Controllers\LandingController::class, 'pricing'])
            ->name('landing.pricing.direct');
        Route::get('/about', [\App\Http\Controllers\LandingController::class, 'about'])
            ->name('landing.about.direct');
        Route::get('/contact', [\App\Http\Controllers\LandingController::class, 'contact'])
            ->name('landing.contact.direct');
    });

    // Regular landing routes with standard middleware (fallback)
    require __DIR__.'/landing.php';
}

// Root route handler - With ensure-landing middleware to intercept for main domains
// These routes have been moved up to be registered before any auth middleware
// to ensure they always have the highest priority

// Modificar la ruta fallback para que solo se aplique a dominios principales
if ($isMainDomain) {
    Route::middleware(['web', 'ensure-landing', 'bypass-tenancy'])->get('/', function () {
        return app(\App\Http\Controllers\LandingController::class)->index();
    })->name('home');
}

// Locale switcher route from landing.php takes precedence for both main and subdomains
Route::get('/set-locale/{locale}', [App\Http\Controllers\LocaleController::class, 'setLocale'])->name('set-locale');

// Appearance setter route
Route::post('/appearance/{mode}', [App\Http\Controllers\AppearanceController::class, 'update'])->name('appearance.update');

// Redirect /dashboard to /spaces when accessed from the main domain
Route::get('/dashboard', function () {
    return redirect()->route('spaces.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// Stripe Webhooks - exempt from CSRF protection
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

// Ruta de teleport para cambiar entre espacios
Route::get('/teleport/{space}', function ($space) {
    // La lógica está en el middleware TeleportToSpace
    return redirect()->route('tenant.dashboard');
})->middleware(['auth', 'teleport'])->name('teleport');

// Ruta de logout global
Route::post('/logout/all', [App\Http\Controllers\Auth\LogoutController::class, 'logoutAll'])
    ->middleware('auth')
    ->name('logout.all');

// API para obtener espacios del usuario actual
Route::get('/api/my-spaces', function () {
    try {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        
        $spaces = app(App\Services\SpaceSwitcherService::class)
            ->getSpacesForUser($user);
            
        return response()->json($spaces);
    } catch (\Exception $e) {
        \Log::error('Error in /api/my-spaces', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json(['error' => 'Internal server error'], 500);
    }
})->middleware(['auth'])->name('api.my-spaces');

Route::middleware('auth')->group(function () {
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Space Routes
    Route::resource('spaces', SpaceController::class);
    Route::get('/spaces/{id}/access', [SpaceController::class, 'access'])->name('spaces.access');
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

// Eliminar o comentar estas líneas - las rutas de tenant ahora se cargan desde RouteServiceProvider
// if (! $isMainDomain) {
//     \Log::info("Loading tenant routes for: {$host}");
//     try {
//         require __DIR__.'/tenant.php';
//     } catch (\Exception $e) {
//         \Log::error('Error loading tenant routes: '.$e->getMessage());
//     }
// }

// Las rutas de landing ya se cargaron al inicio del archivo
// para los dominios principales.
