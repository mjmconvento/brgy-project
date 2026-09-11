<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
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
| Authenticated routes
|--------------------------------------------------------------------------
|
| Every authenticated user is a barangay administrator, so the `auth`
| middleware is the only authorization boundary in the application.
|
*/

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

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

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
});
