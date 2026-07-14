<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmCashControl\Http\Controllers\CashControlApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/controle-caisse', 'crm')
    ->middleware('auth')
    ->name('crm.controle-caisse');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/controle-caisse', CashControlApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.controle-caisse');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/controle-caisse.php', CashControlApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.controle-caisse.legacy');
