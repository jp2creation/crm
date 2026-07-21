<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmCore\Http\Controllers\LegacyPhpApiController;
use Modules\CrmDocuments\Http\Controllers\DocumentApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['throttle:crm-legacy-api', 'crm.compress'];

Route::redirect('/documents', '/documents/promo')
    ->middleware(['auth', 'crm.module:documents,documents.view,documents.manage'])
    ->name('crm.documents.index');

Route::view('/documents/{category}', 'crm')
    ->middleware(['auth', 'crm.module:documents,documents.view,documents.manage'])
    ->where('category', 'promo|fiches-techniques|procedures')
    ->name('crm.documents.category');

Route::get('/documents/file/{document}', [DocumentApiController::class, 'download'])
    ->middleware(['auth', 'crm.module:documents,documents.view,documents.manage'])
    ->whereNumber('document')
    ->name('crm.documents.download');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/documents', DocumentApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:documents'])
    ->name('crm.api.documents');

Route::match(['GET', 'POST'], '/api/mobile/documents', DocumentApiController::class)
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:documents'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.documents');

Route::any('/api/documents.php', LegacyPhpApiController::class)
    ->defaults('crm_legacy_target', '/api/documents')
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.documents.legacy');
