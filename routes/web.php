<?php

use App\Http\Controllers\Crm\AuthController;
use App\Http\Controllers\Crm\AdministrationApiController;
use App\Http\Controllers\Crm\EquipmentRentalApiController;
use App\Http\Controllers\Crm\LeaveApiController;
use App\Http\Controllers\Crm\MobileAuthController;
use App\Http\Controllers\Crm\PageApiController;
use App\Http\Controllers\Crm\ReservationApiController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::options('/api/mobile/{path}', [MobileAuthController::class, 'options'])
    ->where('path', '.*')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.options');

Route::post('/api/mobile/token', [MobileAuthController::class, 'token'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.token');

Route::get('/api/mobile/me', [MobileAuthController::class, 'me'])
    ->middleware('auth:sanctum')
    ->name('crm.api.mobile.me');

Route::post('/api/mobile/logout', [MobileAuthController::class, 'logout'])
    ->middleware('auth:sanctum')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.logout');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/conges', LeaveApiController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.conges');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/conges.php', LeaveApiController::class)
    ->middleware(['crm.legacy_php_api', 'throttle:crm-legacy-api'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.conges.legacy');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/pages', PageApiController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.pages');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/pages.php', PageApiController::class)
    ->middleware(['crm.legacy_php_api', 'throttle:crm-legacy-api'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.pages.legacy');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/reservations', ReservationApiController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.reservations');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/reservations.php', ReservationApiController::class)
    ->middleware(['crm.legacy_php_api', 'throttle:crm-legacy-api'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.reservations.legacy');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipment-rentals', EquipmentRentalApiController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.equipment-rentals');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipment-rentals.php', EquipmentRentalApiController::class)
    ->middleware(['crm.legacy_php_api', 'throttle:crm-legacy-api'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.equipment-rentals.legacy');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/administration', AdministrationApiController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.administration');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/administration.php', AdministrationApiController::class)
    ->middleware(['crm.legacy_php_api', 'throttle:crm-legacy-api'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.administration.legacy');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::view('/', 'crm')->name('crm.home');
    Route::view('/conges', 'crm')->name('crm.conges');
    Route::view('/administration/{section?}', 'crm')
        ->where('section', '.*')
        ->name('crm.administration');
    Route::view('/pages-crm', 'crm-pages')->name('crm.pages');
    Route::view('/pages-crm/{slug}', 'crm-pages')
        ->where('slug', '[A-Za-z0-9_-]+')
        ->name('crm.pages.show');

    Route::view('/{path}', 'crm')
        ->where('path', '^(?!admin(?:/|$)|api(?:/|$)|assets(?:/|$)|build(?:/|$)|filament(?:/|$)|livewire(?:/|$)|storage(?:/|$)|up$).*$')
        ->name('crm.spa');
});
