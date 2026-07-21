<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmAdministration\Http\Controllers\AdministrationApiController;
use Modules\CrmCore\Http\Controllers\LegacyPhpApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['throttle:crm-legacy-api', 'crm.compress'];

Route::view('/administration/{section?}', 'crm')
    ->middleware(['auth', 'crm.module:administration,platform.manage_users,platform.manage_modules,platform.manage_sites,platform.manage_roles,pages.manage'])
    ->where('section', '.*')
    ->name('crm.administration');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/administration', AdministrationApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:administration'])
    ->name('crm.api.administration');

Route::match(['GET', 'POST'], '/api/mobile/administration', AdministrationApiController::class)
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:administration'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.administration');

Route::any('/api/administration.php', LegacyPhpApiController::class)
    ->defaults('crm_legacy_target', '/api/administration')
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.administration.legacy');
