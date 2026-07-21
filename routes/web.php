<?php

use App\Http\Controllers\BlockedLegacyPhpApiController;
use Illuminate\Support\Facades\Route;
use Modules\CrmCore\Http\Controllers\AuthController;

Route::any('/api/{legacyPhpPath}.php', BlockedLegacyPhpApiController::class)
    ->where('legacyPhpPath', '.*')
    ->name('crm.api.legacy-php-blocked');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:crm-login');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:web')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::view('/', 'crm')->name('crm.home');

    Route::view('/{path}', 'crm')
        ->where('path', '^(?!admin(?:/|$)|api(?:/|$)|assets(?:/|$)|build(?:/|$)|filament(?:/|$)|livewire(?:/|$)|storage(?:/|$)|up$).*$')
        ->name('crm.spa');
});
