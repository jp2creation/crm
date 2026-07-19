<?php

use Illuminate\Support\Facades\Route;
use Modules\CrmTapisRomus\Http\Controllers\TapisRomusController;

Route::get('/tapis-romus', TapisRomusController::class)
    ->middleware(['auth', 'crm.module:tapis-romus'])
    ->name('crm.tapis-romus');
