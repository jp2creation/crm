<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmPages\Http\Controllers\PageApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/pages-crm', 'crm')
    ->middleware('auth')
    ->name('crm.pages');

Route::view('/pages-crm/{slug}', 'crm')
    ->middleware('auth')
    ->where('slug', '[A-Za-z0-9_-]+')
    ->name('crm.pages.show');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/pages', PageApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.pages');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/pages.php', PageApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.pages.legacy');
