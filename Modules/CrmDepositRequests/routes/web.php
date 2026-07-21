<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmDepositRequests\Http\Controllers\DepositRequestApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];

Route::view('/demandes-acompte', 'crm')
    ->middleware(['auth', 'crm.module:demandes-acompte,deposit_requests.view,deposit_requests.create,deposit_requests.manage,deposit_requests.validate'])
    ->name('crm.deposit-requests');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/demandes-acompte', DepositRequestApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:demandes-acompte'])
    ->name('crm.api.deposit-requests');

Route::match(['GET', 'POST'], '/api/mobile/demandes-acompte', DepositRequestApiController::class)
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:demandes-acompte'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.demandes-acompte');
