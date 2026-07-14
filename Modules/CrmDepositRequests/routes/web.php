<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmDepositRequests\Http\Controllers\DepositRequestApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/demandes-acompte', 'crm')
    ->middleware('auth')
    ->name('crm.deposit-requests');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/demandes-acompte', DepositRequestApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.deposit-requests');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/demandes-acompte.php', DepositRequestApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.deposit-requests.legacy');
