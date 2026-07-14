<?php

use Illuminate\Support\Facades\Route;

Route::view('/tapis-romus', 'crm')
    ->middleware('auth')
    ->name('crm.tapis-romus');
