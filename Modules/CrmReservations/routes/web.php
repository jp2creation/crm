<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmReservations\Http\Controllers\ReservationApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/reservations', 'crm')
    ->middleware(['auth', 'crm.module:reservations,reservations.view,reservations.create,reservations.manage_vehicles'])
    ->name('crm.reservations');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/reservations', ReservationApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.reservations');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/reservations.php', ReservationApiController::class)
    ->middleware([...$crmLegacyApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.reservations.legacy');
