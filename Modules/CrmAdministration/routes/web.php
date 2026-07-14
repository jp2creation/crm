<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmAdministration\Http\Controllers\AdministrationApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/administration/{section?}', 'crm')
    ->middleware('auth')
    ->where('section', '.*')
    ->name('crm.administration');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/administration', AdministrationApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.administration');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/administration.php', AdministrationApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.administration.legacy');
