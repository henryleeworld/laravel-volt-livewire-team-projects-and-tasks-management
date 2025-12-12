<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Livewire\Auth\AcceptInvitation;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Laravel\Fortify\Features;

Route::redirect('/', '/login');
Route::middleware(['guest'])->group(function () {
    Route::get('auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('socialite.redirect');
    Route::get('auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('socialite.callback');

    Route::get('invitations/{invitation}/accept', AcceptInvitation::class)
        ->name('invitations.accept')
        ->middleware('signed');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    Route::resource('users', UserController::class)
        ->only(['index', 'create', 'store', 'destroy']);

    Route::get('activity-log', [ActivityLogController::class, 'index'])
        ->name('activity-log.index');

    Route::resource('tasks', TaskController::class)
        ->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::resource('projects', ProjectController::class);

    Route::controller(BillingController::class)->group(function () {
        Route::get('billing', 'index')->name('billing.index')->can('manage-billing');
        Route::post('billing/subscribe', 'subscribe')->name('billing.subscribe')->can('manage-billing');
        Route::post('billing/portal', 'portal')->name('billing.portal')->can('manage-billing');
        Route::post('billing/cancel', 'cancel')->name('billing.cancel')->can('manage-billing');
        Route::post('billing/resume', 'resume')->name('billing.resume')->can('manage-billing');
    });

    Route::get('billing/success', function () {
        return redirect()->route('billing.index')
            ->with('success', 'Subscription activated successfully!');
    })->name('billing.success');

    Route::get('billing/checkout-cancel', function () {
        return redirect()->route('billing.index')
            ->with('info', 'Checkout cancelled.');
    })->name('billing.checkout-cancel');
});

Route::post('stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('cashier.webhook');
