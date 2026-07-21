<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmCashControl\Http\Controllers\CashControlApiController;
use Modules\CrmCore\Http\Controllers\LegacyPhpApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['throttle:crm-legacy-api', 'crm.compress'];

Route::view('/controle-caisse', 'crm')
    ->middleware(['auth', 'crm.module:controle-caisse,controle_caisse.view,controle_caisse.manage'])
    ->name('crm.controle-caisse');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/controle-caisse', CashControlApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:controle-caisse'])
    ->name('crm.api.controle-caisse');

Route::match(['GET', 'POST'], '/api/mobile/controle-caisse', CashControlApiController::class)
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:controle-caisse'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.controle-caisse');

Route::any('/api/controle-caisse.php', LegacyPhpApiController::class)
    ->defaults('crm_legacy_target', '/api/controle-caisse')
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.controle-caisse.legacy');
