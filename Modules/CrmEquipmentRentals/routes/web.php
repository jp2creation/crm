<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Modules\CrmEquipmentRentals\Http\Controllers\EquipmentRentalApiController;

$crmApiMiddleware = ['throttle:crm-api', 'crm.compress'];

Route::view('/locations-materiel', 'crm')
    ->middleware(['auth', 'crm.module:locations-materiel,equipment_rentals.view,equipment_rentals.create,equipment_rentals.manage_items'])
    ->name('crm.locations-materiel');

Route::view('/locations-materiel/{rentalPath}', 'crm')
    ->where('rentalPath', '.*')
    ->middleware(['auth', 'crm.module:locations-materiel,equipment_rentals.view,equipment_rentals.create,equipment_rentals.manage_items'])
    ->name('crm.locations-materiel.deep-link');

Route::get('/api/equipment-rentals/bootstrap', EquipmentRentalApiController::class)
    ->defaults('crm_action', 'bootstrap_light')
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->name('crm.api.equipment-rentals.bootstrap');

Route::get('/api/equipment-rentals/users', EquipmentRentalApiController::class)
    ->defaults('crm_action', 'users')
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->name('crm.api.equipment-rentals.users');

Route::get('/api/equipment-rentals/items', EquipmentRentalApiController::class)
    ->defaults('crm_action', 'equipment_items')
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->name('crm.api.equipment-rentals.items');

Route::get('/api/equipment-rentals/categories', EquipmentRentalApiController::class)
    ->defaults('crm_action', 'equipment_categories')
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->name('crm.api.equipment-rentals.categories');

Route::match(['GET', 'POST', 'OPTIONS'], '/api/equipment-rentals', EquipmentRentalApiController::class)
    ->middleware([...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->name('crm.api.equipment-rentals');

Route::get('/api/mobile/equipment-rentals/bootstrap', EquipmentRentalApiController::class)
    ->defaults('crm_action', 'bootstrap_light')
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->withoutMiddleware([PreventRequestForgery::class])
    ->name('crm.api.mobile.equipment-rentals.bootstrap');

Route::get('/api/mobile/equipment-rentals/users', EquipmentRentalApiController::class)
    ->defaults('crm_action', 'users')
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->withoutMiddleware([PreventRequestForgery::class])
    ->name('crm.api.mobile.equipment-rentals.users');

Route::get('/api/mobile/equipment-rentals/items', EquipmentRentalApiController::class)
    ->defaults('crm_action', 'equipment_items')
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->withoutMiddleware([PreventRequestForgery::class])
    ->name('crm.api.mobile.equipment-rentals.items');

Route::get('/api/mobile/equipment-rentals/categories', EquipmentRentalApiController::class)
    ->defaults('crm_action', 'equipment_categories')
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->withoutMiddleware([PreventRequestForgery::class])
    ->name('crm.api.mobile.equipment-rentals.categories');

Route::match(['GET', 'POST'], '/api/mobile/equipment-rentals', EquipmentRentalApiController::class)
    ->middleware(['auth:sanctum', ...$crmApiMiddleware, 'crm.mobile_scope:crm:module:locations-materiel'])
    ->withoutMiddleware([PreventRequestForgery::class])
    ->name('crm.api.mobile.equipment-rentals');
