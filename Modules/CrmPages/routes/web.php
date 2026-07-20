<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmPages\Http\Controllers\PageApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/pages-crm', 'crm')
    ->middleware(['auth', 'crm.module:pages-crm,pages.view,pages.manage'])
    ->name('crm.pages');

Route::view('/pages-crm/{slug}', 'crm')
    ->middleware(['auth', 'crm.module:pages-crm,pages.view,pages.manage'])
    ->where('slug', '[A-Za-z0-9_-]+')
    ->name('crm.pages.show');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/pages', PageApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:pages-crm'])
    ->name('crm.api.pages');

Route::match(['GET', 'POST'], '/api/mobile/pages', PageApiController::class)
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:pages-crm'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.pages');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/pages.php', PageApiController::class)
    ->middleware([...$crmLegacyApiMiddleware, 'crm.mobile_scope:crm:module:pages-crm'])
    ->name('crm.api.pages.legacy');
