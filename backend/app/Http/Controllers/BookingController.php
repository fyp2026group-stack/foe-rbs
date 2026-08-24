<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\LocalResourceServiceClient as Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\BookingOTPMail;
use Carbon\Carbon;
use Exception;

class BookingController
{
    public function index(): JsonResponse
    {
        $cacheKey = 'all_bookings';
        if (Cache::supportsTags()) {
            $bookings = Cache::tags(['bookings'])->remember($cacheKey, 60 * 60, function () {
                return Booking::with('details')->orderBy('booking_date', 'desc')->get();
            });
        } else {
            $bookings = Cache::remember($cacheKey, 60 * 60, function () {
                return Booking::with('details')->orderBy('booking_date', 'desc')->get();
            });
        }
        return response()->json($bookings);
    }

    public function myBookings(Request $request): JsonResponse
    {
        $userEmail = $request->user()?->email ?? $request->header('X-User-Email');
        
        if (!$userEmail) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $bookings = Booking::with('details')
            ->where('user_email', $userEmail)
            ->orderBy('booking_date', 'desc')
            ->get();
            
        return response()->json(['bookings' => $bookings]);
    }

    public function guestLookup(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        
        $bookings = Booking::with('details')
            ->where('user_email', $request->query('email'))
            ->whereIn('status', ['Requested_by_Guest', 'Pending', 'Confirmed', 'Approved', 'Rejected'])
            ->orderBy('booking_date', 'desc')
            ->get();
            
        return response()->json(['bookings' => $bookings]);
    }

    public function show($id): JsonResponse
    {
        $booking = Booking::with('details')->findOrFail($id);
        $resourceDetails = [];
        $bookingItemDetails = [];
        $resourceServiceUrl = 'local://resource/api';

        foreach ($booking->details as $detail) {
            if ($detail->item_type === 'resource') {
                $resourceResponse = Http::timeout(10)->get("{$resourceServiceUrl}/resources/{$detail->item_id}");
                if ($resourceResponse->successful()) {
                    $resource = $resourceResponse->json();
                    $resourceDetails[] = array_merge($resource, [
                        'price_per_hour' => $detail->price_per_hour,
                        'hours' => $detail->hours,
                        'subtotal' => $detail->subtotal,
                    ]);
                }
            } elseif ($detail->item_type === 'booking_item') {
                $itemResponse = Http::timeout(10)->get("{$resourceServiceUrl}/booking-items/{$detail->item_id}");
                if ($itemResponse->successful()) {
                    $item = $itemResponse->json();
                    $bookingItemDetails[] = array_merge($item, [
                        'price_per_hour' => $detail->price_per_hour,
                        'quantity' => $detail->quantity,
                        'hours' => $detail->hours,
                        'subtotal' => $detail->subtotal,
                    ]);
                }
            }
        }

        return response()->json([
            'booking' => $booking,
            'resource_details' => $resourceDetails,
            'booking_item_details' => $bookingItemDetails,
        ]);
    }

    /**
     * Step 1: Create a Pending Booking and Send OTP
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'user_email' => 'required|email',
            'phone' => 'required|string|max:20',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
            'resources' => 'nullable|array',
            'resources.*.resource_id' => 'required_with:resources|integer',
            'booking_items' => 'nullable|array',
            'booking_items.*.item_id' => 'required_with:booking_items|integer',
            'booking_items.*.quantity' => 'required_with:booking_items|integer|min:1'
        ]);

        if (empty($validated['resources']) && empty($validated['booking_items'])) {
            return response()->json(['message' => 'At least one resource or item is required'], 422);
        }

        DB::beginTransaction();
        try {
            $otpCode = Booking::generateOTP();
            
            $userType = $this->userType($request, $validated['user_email']);
            \Log::info("Resolved User Type: {$userType}");

            // 2. Add 'user_type' to the create array
            $booking = Booking::create([
                'user_id'          => $validated['user_id'],
                'user_email'       => $validated['user_email'],
                'phone'            => $validated['phone'],
                'user_type'        => $userType, // Add this line here!
                'booking_reference'=> Booking::generateReference(),
                'booking_date'     => $validated['booking_date'],
                'start_time'       => $validated['start_time'],
                'end_time'         => $validated['end_time'],
                'total_amount'     => 0,
                'status'           => 'Pending_for_Verification',
                'is_verified'      => false,
                'notes'            => $validated['notes'] ?? null
            ]);

            // 2. Store original request data and OTP in cache linked to the ID
            Cache::put("booking_otp_{$booking->id}", [
                'data' => $validated,
                'otp' => $otpCode
            ], now()->addMinutes(10));

            Mail::to($validated['user_email'])->send(new BookingOTPMail($otpCode, "TEMP", 10));

            \Log::info("OTP '{$otpCode}' generated and cached for booking ID: {$booking->id}");

            DB::commit();

            return response()->json([
                'message' => 'OTP sent to email.',
                'booking_id' => $booking->id,
                'otp_code_for_testing' => $otpCode // Remove in production
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Booking store failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to initiate booking', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Step 2: Verify OTP using {id} and Finalize Booking
     */
    public function verifyOTP(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'otp_code' => 'required|string|size:6'
        ]);

        $cacheKey = "booking_otp_{$id}";
        $cachedData = Cache::get($cacheKey);

        \Log::info("OTP Verification Attempt - Booking ID: {$id}");
        \Log::info("Input OTP: '" . $validated['otp_code'] . "'");

        if (!$cachedData) {
            \Log::warning("Cache miss for key: {$cacheKey}. OTP may have expired.");
            return response()->json(['message' => 'Invalid or expired OTP (Session timeout)'], 422);
        }

        $cachedOtp = (string) $cachedData['otp'];
        $inputOtp = (string) $validated['otp_code'];

        \Log::info("Cached OTP: '{$cachedOtp}'");

        if (trim($cachedOtp) !== trim($inputOtp)) {
            \Log::warning("OTP Mismatch for booking {$id}. Expected: '{$cachedOtp}', Received: '{$inputOtp}'");
            return response()->json(['message' => 'Invalid OTP. Please check your email.'], 422);
        }

        $booking = Booking::findOrFail($id);
        $data = $cachedData['data'];

        DB::beginTransaction();
        try {
            $userType = $this->userType($request, $data['user_email']);
            \Log::info("Resolved User Type: {$userType}");
            $start = Carbon::parse($data['start_time']);
            $end = Carbon::parse($data['end_time']);
            $hours = $start->diffInMinutes($end) / 60;

            $totalAmount = 0;
            $detailsToCreate = [];
            $resourceServiceUrl = 'local://resource/api';

            // Process Resources
            if (!empty($data['resources'])) {
                foreach ($data['resources'] as $resData) {
                    $resId = $resData['resource_id'];
                    $resResp = Http::get("{$resourceServiceUrl}/resources/{$resId}");
                    if (!$resResp->successful()) throw new Exception("Resource {$resId} not found");
                    
                    $res = $resResp->json();
                    $price = ($userType === 'internal') ? 0 : $res['base_price'];
                    $subtotal = $price * $hours;
                    $totalAmount += $subtotal;

                    $detailsToCreate[] = [
                        'item_type' => 'resource',
                        'item_id' => $resId,
                        'item_name' => $res['name'],
                        'quantity' => 1,
                        'price_per_hour' => $price,
                        'hours' => $hours,
                        'subtotal' => $subtotal
                    ];
                }
            }

            // Process Booking Items
            if (!empty($data['booking_items'])) {
                foreach ($data['booking_items'] as $itemData) {
                    $itemId = $itemData['item_id'];
                    $qty = $itemData['quantity'];
                    $itemResp = Http::get("{$resourceServiceUrl}/booking-items/{$itemId}");
                    if (!$itemResp->successful()) throw new Exception("Item {$itemId} not found");

                    $item = $itemResp->json();
                    $price = ($userType === 'internal') ? 0 : $item['price_per_hour'];
                    $subtotal = $price * $hours * $qty;
                    $totalAmount += $subtotal;

                    $detailsToCreate[] = [
                        'item_type' => 'booking_item',
                        'item_id' => $itemId,
                        'item_name' => $item['name'],
                        'item_code' => $item['item_code'],
                        'quantity' => $qty,
                        'price_per_hour' => $price,
                        'hours' => $hours,
                        'subtotal' => $subtotal
                    ];
                }
            }

            // Update original booking record
            $status = ($userType === 'external') ? 'Requested_by_Guest' : 'Pending';

            $booking->update([
                'status' => $status,
                'total_amount' => $totalAmount,
                'is_verified' => true,
                'confirmed_at' => now(), 
                'user_type' => $userType
            ]);

            $booking->details()->createMany($detailsToCreate);
            
            // Re-load with details for reservation
            $booking->load('details');
            
            if ($status !== 'Requested_by_Guest') {
                $this->reserveInventory($booking);
            } else {
                // It's a guest request: Notify all assigned admins
                // Get the admin ID from the first resource (assuming 1 resource)
                $firstResourceDetail = $booking->details->where('item_type', 'resource')->first();
                if ($firstResourceDetail) {
                    $resId = $firstResourceDetail->item_id;
                    $resResp = Http::get("{$resourceServiceUrl}/resources/{$resId}");
                    if ($resResp->successful()) {
                        $resData = $resResp->json();
                        
                        $assignedAdminIds = [];
                        $ids = $resData['assigned_admin_ids'] ?? [];
                        if (!empty($ids) && is_array($ids)) {
                            $assignedAdminIds = array_map('intval', $ids);
                        } elseif (!empty($resData['assigned_admin_id'])) {
                            $assignedAdminIds = [(int)$resData['assigned_admin_id']];
                        }
                        
                        foreach ($assignedAdminIds as $adminId) {
                            $adminUserResp = Http::get("local://auth/api/internal/users/{$adminId}");
                            if ($adminUserResp->successful()) {
                                $adminEmail = $adminUserResp->json()['email'] ?? null;
                                if ($adminEmail) {
                                    Mail::to($adminEmail)->send(new \App\Mail\BookingRequestedMail($booking));
                                }
                            }
                        }
                    }
                }
            }

            Cache::forget($cacheKey);
            $this->clearBookingCache();

            DB::commit();
            return response()->json(['message' => 'Booking success!', 'booking' => $booking], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Verification failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function resendOTP(Request $request, $id): JsonResponse
    {
        $cacheKey = "booking_otp_{$id}";
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            return response()->json(['message' => 'Session expired. Please restart booking.'], 422);
        }

        $otpCode = Booking::generateOTP();
        $cachedData['otp'] = $otpCode;
        Cache::put($cacheKey, $cachedData, now()->addMinutes(10));

        Mail::to($cachedData['data']['user_email'])->send(new BookingOTPMail($otpCode, "TEMP", 10));

        return response()->json(['message' => 'OTP resent successfully']);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,Confirmed,Cancelled,Completed,Requested_by_Guest,Rejected'
        ]);

        $booking = Booking::with('details')->findOrFail($id);
        $adminId = $request->user()?->id;
        $adminRole = $request->user()?->roles()->value('name');

        DB::beginTransaction();
        try {
            \Log::info("Updating status for booking {$id} to {$validated['status']}. Current status: {$booking->status}, Admin Role: {$adminRole}, Admin ID: {$adminId}");
            \Log::info("Details count: " . $booking->details->count());

            // Handle multi-admin confirmation flow when setting status to Confirmed
            if ($validated['status'] === 'Confirmed') {
                if ($booking->status === 'Confirmed') {
                    return response()->json([
                        'message' => 'Booking is already confirmed.',
                        'booking' => $booking
                    ]);
                }

                // If user is a Master Admin, bypass individual confirmation and confirm at once
                if ($adminRole === 'Master Admin') {
                    \Log::info("Bypassing confirmation check because requester is Master Admin.");
                    $this->reserveInventory($booking);
                    $booking->update(['status' => 'Confirmed']);
                    
                    // Mark as confirmed by all assigned admins (or store empty/master marker)
                    $booking->update(['confirmed_by_admins' => ['master']]);

                    if (in_array('Confirmed', ['Pending', 'Confirmed', 'Rejected', 'Cancelled'])) {
                        Mail::to($booking->user_email)->send(new \App\Mail\BookingStatusUpdatedMail($booking));
                    }
                    $this->clearBookingCache();
                    DB::commit();

                    return response()->json([
                        'message' => 'Booking confirmed successfully by Master Admin.',
                        'booking' => $booking->fresh('details')
                    ]);
                }

                // If user is regular Admin
                if ($adminRole === 'Admin') {
                    // Fetch all assigned admin IDs for all resources in this booking
                    $allAssignedAdminIds = [];
                    foreach ($booking->details as $detail) {
                        if ($detail->item_type === 'resource') {
                            $resResp = Http::get("{$resourceServiceUrl}/resources/{$detail->item_id}");
                            if ($resResp->successful()) {
                                $resData = $resResp->json();
                                $ids = $resData['assigned_admin_ids'] ?? [];
                                if (!empty($ids) && is_array($ids)) {
                                    $allAssignedAdminIds = array_merge($allAssignedAdminIds, array_map('intval', $ids));
                                } elseif (!empty($resData['assigned_admin_id'])) {
                                    $allAssignedAdminIds[] = (int)$resData['assigned_admin_id'];
                                }
                            }
                        }
                    }
                    $allAssignedAdminIds = array_unique($allAssignedAdminIds);

                    \Log::info("Required assigned admins for booking confirmation: " . json_encode($allAssignedAdminIds));

                    // If there are assigned admins, check if current admin is one of them
                    if (!empty($allAssignedAdminIds)) {
                        if (!$adminId || !in_array($adminId, $allAssignedAdminIds)) {
                            return response()->json([
                                'message' => 'Forbidden. You are not an assigned admin for any resource in this booking.'
                            ], 403);
                        }

                        // Record current admin's confirmation
                        $confirmed = $booking->confirmed_by_admins ?? [];
                        if (!in_array($adminId, $confirmed)) {
                            $confirmed[] = $adminId;
                        }
                        $booking->update(['confirmed_by_admins' => $confirmed]);

                        \Log::info("Current confirmations: " . json_encode($confirmed));

                        // Check if all assigned admins have confirmed
                        $allConfirmed = true;
                        foreach ($allAssignedAdminIds as $id) {
                            if (!in_array($id, $confirmed)) {
                                $allConfirmed = false;
                                break;
                            }
                        }

                        if (!$allConfirmed) {
                            // Committing the partial confirmation state to the database, but keeping status Pending
                            $this->clearBookingCache();
                            DB::commit();

                            return response()->json([
                                'message' => 'Your confirmation has been recorded. Waiting for other assigned admins to confirm.',
                                'booking' => $booking->fresh('details')
                            ]);
                        }
                    }
                }
            }

            // Normal flow (If Master Admin, or regular Admin and all confirmed, or other status changes)
            if ($validated['status'] === 'Confirmed' && $booking->status !== 'Confirmed') {
                $this->reserveInventory($booking);
            }

            if ($validated['status'] === 'Cancelled') {
                $this->releaseInventory($booking->id);
            }

            $booking->update(['status' => $validated['status']]);

            if (in_array($validated['status'], ['Pending', 'Confirmed', 'Rejected', 'Cancelled'])) {
                Mail::to($booking->user_email)->send(new \App\Mail\BookingStatusUpdatedMail($booking));
            }

            $this->clearBookingCache();
            DB::commit();

            return response()->json([
                'message' => 'Status updated successfully',
                'booking' => $booking->fresh('details')
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function cancel($id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        if (in_array($booking->status, ['Cancelled', 'Completed'])) {
            return response()->json(['message' => 'Cannot cancel this booking'], 422);
        }
        $booking->update(['status' => 'Cancelled']);
        $this->releaseInventory($booking->id);
        $this->clearBookingCache();
        return response()->json(['message' => 'Booking cancelled', 'booking' => $booking]);
    }

    public function getByAssignedAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'admin_id' => 'required|integer',
            'status' => 'nullable|in:Pending,Confirmed,Cancelled,Completed,Requested_by_Guest,Rejected',
        ]);

        $resourceServiceUrl = 'local://resource/api';
        $resourcesResp = Http::get("{$resourceServiceUrl}/resources");
        
        if (!$resourcesResp->successful()) {
            return response()->json(['message' => 'Service error'], 500);
        }

        $resourcesMap = collect($resourcesResp->json());
        
        // Ensure comparison is safe by casting to integer
        $adminResourceIds = $resourcesMap->filter(function($r) use ($validated) {
            $adminId = (int)$validated['admin_id'];
            if (isset($r['assigned_admin_ids']) && is_array($r['assigned_admin_ids'])) {
                $ids = array_map('intval', $r['assigned_admin_ids']);
                if (in_array($adminId, $ids)) {
                    return true;
                }
            }
            return isset($r['assigned_admin_id']) && (int)$r['assigned_admin_id'] === $adminId;
        })->pluck('id')->toArray();

        $statusStr = $validated['status'] ?? 'all';
        $cacheKey = "bookings_admin_{$validated['admin_id']}_{$statusStr}";

        if (Cache::supportsTags()) {
            $bookingsData = Cache::tags(['bookings'])->remember($cacheKey, 60 * 60, function () use ($validated, $adminResourceIds) {
                return $this->fetchBookingsByAdmin($validated, $adminResourceIds);
            });
        } else {
            $bookingsData = Cache::remember($cacheKey, 60 * 60, function () use ($validated, $adminResourceIds) {
                return $this->fetchBookingsByAdmin($validated, $adminResourceIds);
            });
        }

        return response()->json($bookingsData);
    }

    public function getByResourceId($resourceId): JsonResponse
    {
        $bookings = Booking::whereHas('details', function ($q) use ($resourceId) {
            $q->where('item_type', 'resource')->where('item_id', $resourceId);
        })->with('details')->orderBy('booking_date', 'desc')->get();

        return response()->json($bookings);
    }

    public function destroy($id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $resourceServiceUrl = 'local://resource/api';

        DB::beginTransaction();
        try {
            // Release stock logs if any existed for this booking
            $this->releaseInventory($booking->id);

            // Delete children first 
            $booking->details()->delete();
            $booking->delete();

            $this->clearBookingCache();

            DB::commit();
            return response()->json([
                'message' => 'Booking deleted and stock released'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function reserveInventory(Booking $booking): void
    {
        $resourceServiceUrl = 'local://resource/api';
        
        foreach ($booking->details as $detail) {
            if ($detail->item_type === 'booking_item') {
                $reserveResponse = Http::post("{$resourceServiceUrl}/items/reserve", [
                    'item_id' => $detail->item_id,
                    'booking_id' => $booking->id,
                    'date' => $booking->booking_date,
                    'start_time' => \Carbon\Carbon::parse($booking->start_time)->format('H:i'),
                    'end_time' => \Carbon\Carbon::parse($booking->end_time)->format('H:i'),
                    'quantity' => $detail->quantity,
                ]);

                if ($reserveResponse->status() === 422) {
                    throw new Exception("The item '{$detail->item_name}' is fully booked for this time slot.");
                }

                if (!$reserveResponse->successful()) {
                    throw new Exception("Inventory Service Error: " . ($reserveResponse->json()['message'] ?? 'Unknown error'));
                }
            }
        }
    }

    private function releaseInventory(int $bookingId): void
    {
        $resourceServiceUrl = 'local://resource/api';
        Http::post("{$resourceServiceUrl}/items/release", [
            'booking_id' => $bookingId
        ]);
    }

    /**
     * Clear all booking related cache safely
     */
    private function clearBookingCache(): void
    {
        if (Cache::supportsTags()) {
            Cache::tags(['bookings'])->flush();
        } else {
            // Fallback: Clear main keys if tags not supported
            Cache::forget('all_bookings');
            // Note: Admin-specific caches might persist until expiration if tags unavailable
        }
    }

    private function userType(Request $request, string $email): string
    {
        $user = $request->user();
        if ($user && $user->email === $email && $user->roles()->whereIn('name', ['Master Admin', 'Admin', 'User'])->exists()) {
            return 'internal';
        }
        return 'external';
    }

    /**
     * Helper to fetch and filter bookings for admin
     */
    private function fetchBookingsByAdmin(array $validated, array $adminResourceIds): array
    {
        $filtered = Booking::with('details')
            ->when($validated['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->get()
            ->filter(function ($b) use ($adminResourceIds) {
                return $b->details->contains(fn($d) => $d->item_type === 'resource' && in_array($d->item_id, $adminResourceIds));
            });
        return ['total' => $filtered->count(), 'bookings' => $filtered->values()];
    }
}
