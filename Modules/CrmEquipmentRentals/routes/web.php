<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\CrmEquipmentRentals\Http\Controllers\EquipmentRentalApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];
$crmLegacyApiMiddleware = ['crm.legacy_php_api', 'throttle:crm-legacy-api', 'crm.compress'];

Route::view('/locations-materiel', 'crm')
    ->middleware(['auth', 'crm.module:locations-materiel,equipment_rentals.view,equipment_rentals.create,equipment_rentals.manage_items'])
    ->name('crm.locations-materiel');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipment-rentals', EquipmentRentalApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->name('crm.api.equipment-rentals');

Route::match(['GET', 'POST'], '/api/mobile/equipment-rentals', EquipmentRentalApiController::class)
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('crm.api.mobile.equipment-rentals');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipment-rentals.php', EquipmentRentalApiController::class)
    ->middleware([...$crmLegacyApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->name('crm.api.equipment-rentals.legacy');
