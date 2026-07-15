<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmSalesTours\Http\Controllers\SalesTourApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/rapport-visite', 'crm')
    ->middleware('auth')
    ->name('crm.visit-report');

Route::view('/tournees-representants', 'crm')
    ->middleware('auth')
    ->name('crm.sales-tours');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/tournees-representants', SalesTourApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.sales-tours');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/tournees-representants.php', SalesTourApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.sales-tours.legacy');
