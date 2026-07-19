<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmSalesTours\Http\Controllers\SalesTourApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/rapport-visite', 'crm')
    ->middleware(['auth', 'crm.module:tournees-representants,sales_tours.view,sales_tours.create,sales_tours.report,sales_tours.manage'])
    ->name('crm.visit-report');

Route::view('/tournees-representants', 'crm')
    ->middleware(['auth', 'crm.module:tournees-representants,sales_tours.view,sales_tours.create,sales_tours.report,sales_tours.manage'])
    ->name('crm.sales-tours');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/tournees-representants', SalesTourApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:tournees-representants'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.sales-tours');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/tournees-representants.php', SalesTourApiController::class)
    ->middleware([...$crmLegacyApiMiddleware, 'crm.mobile_scope:crm:module:tournees-representants'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.sales-tours.legacy');
