<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmLeaves\Http\Controllers\LeaveApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];

Route::view('/conges', 'crm')
    ->middleware(['auth', 'crm.module:conges,conges.view,conges.manage'])
    ->name('crm.conges');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/conges', LeaveApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:conges'])
    ->name('crm.api.conges');

Route::match(['GET', 'POST'], '/api/mobile/conges', LeaveApiController::class)
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:conges'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.conges');
