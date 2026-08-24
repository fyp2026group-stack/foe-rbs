<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ResourceTemplateController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPermissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/health', fn () => response()->json(['service' => 'FOE-RBS backend', 'status' => 'running', 'timestamp' => now()]));

// Public API
Route::post('/login', [AuthController::class, 'login']);
Route::post('/guest-login', [AuthController::class, 'guestLogin']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password/email', [AuthController::class, 'sendResetOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);
Route::get('/settings', [SystemSettingsController::class, 'index']);
Route::get('/settings/logo/{filename}', fn (string $filename) => Storage::disk('public')->response('logos/'.$filename));
Route::get('/resources/storage/{path}', fn (string $path) => Storage::disk('public')->response($path))->where('path', '.*');
Route::get('/departments', [DepartmentController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/resources', [ResourceController::class, 'index']);
Route::get('/resources/batch', [ResourceController::class, 'getBatch']);
Route::get('/resources/{id}', [ResourceController::class, 'show']);
Route::get('/booking-items/available', [BookingItemController::class, 'available']);
Route::get('/booking-items/availability', [BookingItemController::class, 'availability']);
Route::get('/bookings/guest-lookup', [BookingController::class, 'guestLookup']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn (Request $request) => response()->json($request->user()->load('roles')));
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:user.delete');
    Route::get('/admins', [UserController::class, 'getAdmins']);
    Route::get('/admins/{id}', fn (int $id) => response()->json(\App\Models\User::with('roles')->findOrFail($id)));
    Route::post('/users/{id}/permissions', [UserPermissionController::class, 'updatePermissions'])->middleware('role:Master Admin');
    Route::get('/users/{id}/permissions', [UserPermissionController::class, 'getPermissions'])->middleware('role:Master Admin');
    Route::get('/users/permissions/overrides', [UserPermissionController::class, 'index'])->middleware('role:Master Admin');

    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::get('/departments/{department}', [DepartmentController::class, 'show']);
    Route::put('/departments/{department}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);
    Route::post('/resources', [ResourceController::class, 'store'])->middleware('permission:manage_resources');
    Route::put('/resources/{id}', [ResourceController::class, 'update'])->middleware('permission:manage_resources');
    Route::post('/resources/{id}', [ResourceController::class, 'update'])->middleware('permission:manage_resources');
    Route::delete('/resources/{id}', [ResourceController::class, 'destroy'])->middleware('permission:manage_resources');
    Route::post('/items/reserve', [ResourceController::class, 'reserve']);
    Route::post('/items/release', [ResourceController::class, 'release']);
    Route::get('/booking-items', [BookingItemController::class, 'index']);
    Route::post('/booking-items', [BookingItemController::class, 'store']);
    Route::get('/booking-items/{id}', [BookingItemController::class, 'show']);
    Route::put('/booking-items/{id}', [BookingItemController::class, 'update']);
    Route::delete('/booking-items/{id}', [BookingItemController::class, 'destroy']);
    Route::get('/resource-templates', [ResourceTemplateController::class, 'index']);
    Route::post('/resource-templates', [ResourceTemplateController::class, 'store']);
    Route::get('/resource-templates/category/{categoryId}', [ResourceTemplateController::class, 'getByCategory']);
    Route::get('/resource-templates/{id}', [ResourceTemplateController::class, 'show']);
    Route::put('/resource-templates/{id}', [ResourceTemplateController::class, 'update']);
    Route::delete('/resource-templates/{id}', [ResourceTemplateController::class, 'destroy']);
    Route::post('/settings', [SystemSettingsController::class, 'update']);
    Route::post('/settings/action/{action}', [SystemSettingsController::class, 'action']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/my', [BookingController::class, 'myBookings']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/admin/assigned', [BookingController::class, 'getByAssignedAdmin'])->middleware('permission:view_assigned_bookings');
    Route::get('/bookings/resource/{resourceId}', [BookingController::class, 'getByResourceId']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{id}/verify-otp', [BookingController::class, 'verifyOTP']);
    Route::post('/bookings/{id}/resend-otp', [BookingController::class, 'resendOTP']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);
});
