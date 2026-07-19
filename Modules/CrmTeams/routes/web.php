<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmTeams\Http\Controllers\TeamApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/equipes', 'crm')
    ->middleware(['auth', 'crm.module:equipes,teams.view'])
    ->name('crm.equipes');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipes', TeamApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:equipes'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.equipes');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipes.php', TeamApiController::class)
    ->middleware([...$crmLegacyApiMiddleware, 'crm.mobile_scope:crm:module:equipes'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.equipes.legacy');
