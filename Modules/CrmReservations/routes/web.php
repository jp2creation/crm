<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmReservations\Http\Controllers\ReservationApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/reservations', 'crm')
    ->middleware(['auth', 'crm.module:reservations,reservations.view,reservations.create,reservations.manage_vehicles'])
    ->name('crm.reservations');

Route::view('/reservations/{reservationPath}', 'crm')
    ->where('reservationPath', '.*')
    ->middleware(['auth', 'crm.module:reservations,reservations.view,reservations.create,reservations.manage_vehicles'])
    ->name('crm.reservations.deep-link');

Route::get('/api/reservations/bootstrap', ReservationApiController::class)
    ->defaults('crm_action', 'bootstrap_light')
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->name('crm.api.reservations.bootstrap');

Route::get('/api/reservations/users', ReservationApiController::class)
    ->defaults('crm_action', 'users')
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->name('crm.api.reservations.users');

Route::get('/api/reservations/vehicles', ReservationApiController::class)
    ->defaults('crm_action', 'vehicles')
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->name('crm.api.reservations.vehicles');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/reservations', ReservationApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->name('crm.api.reservations');

Route::get('/api/mobile/reservations/bootstrap', ReservationApiController::class)
    ->defaults('crm_action', 'bootstrap_light')
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.reservations.bootstrap');

Route::get('/api/mobile/reservations/users', ReservationApiController::class)
    ->defaults('crm_action', 'users')
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.reservations.users');

Route::get('/api/mobile/reservations/vehicles', ReservationApiController::class)
    ->defaults('crm_action', 'vehicles')
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.reservations.vehicles');

Route::match(['GET', 'POST'], '/api/mobile/reservations', ReservationApiController::class)
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.reservations');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/reservations.php', ReservationApiController::class)
    ->middleware([...$crmLegacyApiMiddleware, 'crm.mobile_scope:crm:module:reservations'])
    ->name('crm.api.reservations.legacy');
