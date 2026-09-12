<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BarangayCaptainController;
use App\Http\Controllers\ConstituentController;
use App\Http\Controllers\CriminalRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated, not yet verified
|--------------------------------------------------------------------------
|
| What an account can do before its email address is confirmed: read the
| "check your inbox" page, click the link, ask for another one, fix a mistyped
| address on the profile, and leave. Everything else is behind `verified`.
|
*/

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');

    // `signed` rejects tampered or expired links; the throttle bounds guessing.
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated and verified
|--------------------------------------------------------------------------
|
| Every verified user is a barangay administrator, so `auth` + `verified` is
| the only authorization boundary in the application.
|
*/

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('constituents', ConstituentController::class);
    Route::resource('barangay-captains', BarangayCaptainController::class);

    // Nested + shallow: create/store hang off the constituent, while
    // edit/update/destroy bind the child record directly.
    Route::resource('constituents.taxes', TaxController::class)
        ->shallow()
        ->except(['index', 'show']);

    Route::resource('constituents.criminal-records', CriminalRecordController::class)
        ->shallow()
        ->except(['index', 'show']);
});
