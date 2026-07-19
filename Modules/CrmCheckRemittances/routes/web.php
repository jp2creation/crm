<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmCheckRemittances\Http\Controllers\CheckRemittanceApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/remise-cheques', 'crm')
    ->middleware(['auth', 'crm.module:remise-cheques,check_remittances.view,check_remittances.manage'])
    ->name('crm.remise-cheques');

Route::view('/remise-cheques/{remittance}', 'crm')
    ->middleware(['auth', 'crm.module:remise-cheques,check_remittances.view,check_remittances.manage'])
    ->whereNumber('remittance')
    ->name('crm.remise-cheques.show');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/remise-cheques', CheckRemittanceApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:remise-cheques'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.remise-cheques');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/remise-cheques.php', CheckRemittanceApiController::class)
    ->middleware([...$crmLegacyApiMiddleware, 'crm.mobile_scope:crm:module:remise-cheques'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.remise-cheques.legacy');
