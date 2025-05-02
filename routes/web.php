<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SpaceController;
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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
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
