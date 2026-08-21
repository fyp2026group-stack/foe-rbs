<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;

// Health check
Route::get('/health', function () {
    return response()->json([
        'service' => 'Booking Service',
        'status' => 'running',
        'timestamp' => now()
    ]);
});

// Booking routes
Route::get('/bookings', [BookingController::class, 'index']);
Route::get('/bookings/my', [BookingController::class, 'myBookings']);
Route::get('/bookings/guest-lookup', [BookingController::class, 'guestLookup']);
Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings/{id}', [BookingController::class, 'show']);
Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
Route::get('/bookings/admin/assigned', [BookingController::class, 'getByAssignedAdmin'])->middleware('permission:view_assigned_bookings');
Route::get('/bookings/resource/{resourceId}', [BookingController::class, 'getByResourceId']);
Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);

// OTP verification routes
Route::post('/bookings/{id}/verify-otp', [BookingController::class, 'verifyOTP']);
Route::post('/bookings/{id}/resend-otp', [BookingController::class, 'resendOTP']);