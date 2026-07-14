<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmLeaves\Http\Controllers\LeaveApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/conges', 'crm')
    ->middleware('auth')
    ->name('crm.conges');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/conges', LeaveApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.conges');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/conges.php', LeaveApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.conges.legacy');
