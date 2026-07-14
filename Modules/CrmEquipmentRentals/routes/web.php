<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmEquipmentRentals\Http\Controllers\EquipmentRentalApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/locations-materiel', 'crm')
    ->middleware('auth')
    ->name('crm.locations-materiel');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipment-rentals', EquipmentRentalApiController::class)
    ->middleware($crmApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.equipment-rentals');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipment-rentals.php', EquipmentRentalApiController::class)
    ->middleware($crmLegacyApiMiddleware)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.equipment-rentals.legacy');
