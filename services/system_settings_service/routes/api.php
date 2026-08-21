<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemSettingsController;

// System Settings Routes
Route::get('/settings', [SystemSettingsController::class,'index']);
Route::post('/settings', [SystemSettingsController::class,'update']);
Route::post('/settings/action/{action}', [SystemSettingsController::class,'action']);
