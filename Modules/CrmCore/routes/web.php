<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmCore\Http\Controllers\DashboardApiController;
use Modules\CrmCore\Http\Controllers\LegacyTemplateController;
use Modules\CrmCore\Http\Controllers\MobileAuthController;
use Modules\CrmCore\Http\Controllers\PwaAssetController;

Route::get('/manifest.json', [PwaAssetController::class, 'manifest'])->name('crm.pwa.manifest');
Route::get('/sw.js', [PwaAssetController::class, 'serviceWorker'])->name('crm.pwa.service-worker');
Route::get('/offline.html', [PwaAssetController::class, 'offline'])->name('crm.pwa.offline');

Route::any('/{legacyTemplatePath}', LegacyTemplateController::class)
    ->where('legacyTemplatePath', '^(?:app|dashboard|forms|tables|charts|pages|features|auth|auth-card)(?:/.*)?$')
    ->name('crm.legacy-template-gone');

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLoginApiMiddleware = ['throttle:crm-login', 'crm.compress'];

Route::match(['GET', 'OPTIONS'], '/api/dashboard', DashboardApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.dashboard');

Route::options('/api/mobile/{path}', [MobileAuthController::class, 'options'])
    ->middleware('crm.compress')
    ->where('path', '.*')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.options');

Route::post('/api/mobile/token', [MobileAuthController::class, 'token'])
    ->middleware($crmLoginApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.token');

Route::get('/api/mobile/me', [MobileAuthController::class, 'me'])
    ->middleware(['auth:sanctum', ...$crmApiMiddleware])
    ->name('crm.api.mobile.me');

Route::post('/api/mobile/web-session', [MobileAuthController::class, 'webSession'])
    ->middleware(['auth:sanctum', ...$crmApiMiddleware])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.web-session');

Route::post('/api/mobile/logout', [MobileAuthController::class, 'logout'])
    ->middleware(['auth:sanctum', ...$crmApiMiddleware])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.logout');

Route::get('/mobile/session/{token}', [MobileAuthController::class, 'consumeWebSession'])
    ->middleware(['throttle:crm-api'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('crm.mobile.web-session');
