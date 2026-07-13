<?php

use App\Http\Controllers\Crm\AdministrationApiController;
use App\Http\Controllers\Crm\AuthController;
use App\Http\Controllers\Crm\CashControlApiController;
use App\Http\Controllers\Crm\CheckRemittanceApiController;
use App\Http\Controllers\Crm\EquipmentRentalApiController;
use App\Http\Controllers\Crm\LeaveApiController;
use App\Http\Controllers\Crm\MobileAuthController;
use App\Http\Controllers\Crm\PageApiController;
use App\Http\Controllers\Crm\ReservationApiController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.json', function () {
    return response()
        ->file(public_path('manifest.json'), [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Type' => 'application/manifest+json; charset=utf-8',
        ]);
})->name('crm.pwa.manifest');

Route::get('/sw.js', function () {
    return response()
        ->file(public_path('sw.js'), [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/',
        ]);
})->name('crm.pwa.service-worker');

Route::get('/offline.html', function () {
    return response()
        ->file(public_path('offline.html'), [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
})->name('crm.pwa.offline');

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];
$crmLoginApiMiddleware = ['throttle:crm-login', 'crm.compress'];

Route::options('/api/mobile/{path}', [MobileAuthController::class, 'options'])
    ->middleware('crm.compress')
    ->where('path', '.*')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.options');

Route::post('/api/mobile/token', [MobileAuthController::class, 'token'])
    ->middleware($crmLoginApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.token');

Route::get('/api/mobile/me', [MobileAuthController::class, 'me'])
    ->middleware(['auth:sanctum', ...$crmApiMiddleware])
    ->name('crm.api.mobile.me');

Route::post('/api/mobile/web-session', [MobileAuthController::class, 'webSession'])
    ->middleware(['auth:sanctum', ...$crmApiMiddleware])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.web-session');

Route::post('/api/mobile/logout', [MobileAuthController::class, 'logout'])
    ->middleware(['auth:sanctum', ...$crmApiMiddleware])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.logout');

Route::get('/mobile/session/{token}', [MobileAuthController::class, 'consumeWebSession'])
    ->middleware(['throttle:crm-api'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('crm.mobile.web-session');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/conges', LeaveApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.conges');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/conges.php', LeaveApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.conges.legacy');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/controle-caisse', CashControlApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.controle-caisse');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/controle-caisse.php', CashControlApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.controle-caisse.legacy');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/remise-cheques', CheckRemittanceApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.remise-cheques');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/remise-cheques.php', CheckRemittanceApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.remise-cheques.legacy');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/pages', PageApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.pages');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/pages.php', PageApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.pages.legacy');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/reservations', ReservationApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.reservations');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/reservations.php', ReservationApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.reservations.legacy');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipment-rentals', EquipmentRentalApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.equipment-rentals');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipment-rentals.php', EquipmentRentalApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.equipment-rentals.legacy');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/administration', AdministrationApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.administration');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/administration.php', AdministrationApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.administration.legacy');

Route::any('/{legacyTemplatePath}', fn () => abort(404))
    ->where('legacyTemplatePath', '^(?:app|dashboard|forms|tables|charts|pages|features|auth|auth-card)(?:/.*)?$')
    ->name('crm.legacy-template-gone');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:crm-login');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::view('/', 'crm')->name('crm.home');
    Route::view('/conges', 'crm')->name('crm.conges');
    Route::view('/controle-caisse', 'crm')->name('crm.controle-caisse');
    Route::view('/remise-cheques', 'crm')->name('crm.remise-cheques');
    Route::view('/remise-cheques/{remittance}', 'crm')
        ->whereNumber('remittance')
        ->name('crm.remise-cheques.show');
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
