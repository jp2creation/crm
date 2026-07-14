<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmReservations\Http\Controllers\ReservationApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/reservations', 'crm')
    ->middleware('auth')
    ->name('crm.reservations');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/reservations', ReservationApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.reservations');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/reservations.php', ReservationApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.reservations.legacy');
