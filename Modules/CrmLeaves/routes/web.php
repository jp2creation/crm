<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmLeaves\Http\Controllers\LeaveApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/conges', 'crm')
    ->middleware(['auth', 'crm.module:conges,conges.view,conges.manage'])
    ->name('crm.conges');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/conges', LeaveApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:conges'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.conges');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/conges.php', LeaveApiController::class)
    ->middleware([...$crmLegacyApiMiddleware, 'crm.mobile_scope:crm:module:conges'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.conges.legacy');
