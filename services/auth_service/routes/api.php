<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionController;

// ** Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/guest-login', [AuthController::class, 'guestLogin']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password/email', [AuthController::class, 'sendResetOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

// Internal Microservice route (Only accessible internally within docker network)
Route::get('/internal/users/{id}', function($id) {
    return \App\Models\User::find($id);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // USER: Self Profile Management
    Route::get('/user', function (Request $request) {
        return response()->json($request->user()->load('roles'));
    });
    Route::put('/user/profile', [UserController::class, 'updateProfile']);

    // MASTER ADMIN ONLY: Permission Management
    Route::middleware('role:Master Admin')->group(function () {
        Route::post('/users/{id}/permissions', [UserPermissionController::class, 'updatePermissions']);
        Route::get('/users/{id}/permissions', [UserPermissionController::class, 'getPermissions']);
        Route::get('/users/permissions/overrides', [UserPermissionController::class, 'index']);
    });

    // ADMIN & MASTER ADMIN: General Management
    Route::middleware('role:Master Admin,Admin,User,Guest')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::get('/admins', [UserController::class, 'getAdmins']);
    });

    // SPECIFIC PERMISSION CHECK: Override-Aware Actions
    // Master Admin passes due to '*' ability. 
    // Admin only passes if 'user.delete' is in their token.
    Route::middleware('permission:user.delete')->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});