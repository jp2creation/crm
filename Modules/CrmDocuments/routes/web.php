<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmDocuments\Http\Controllers\DocumentApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::redirect('/documents', '/documents/promo')
    ->middleware('auth')
    ->name('crm.documents.index');

Route::view('/documents/{category}', 'crm')
    ->middleware('auth')
    ->where('category', 'promo|fiches-techniques|procedures')
    ->name('crm.documents.category');

Route::get('/documents/file/{document}', [DocumentApiController::class, 'download'])
    ->middleware('auth')
    ->whereNumber('document')
    ->name('crm.documents.download');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/documents', DocumentApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.documents');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/documents.php', DocumentApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.documents.legacy');
