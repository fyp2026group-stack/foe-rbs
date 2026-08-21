<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Booking;
use App\Models\BookingDetail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MultiAdminConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_admin_booking_confirmation_flow()
    {
        // 1. Setup Http Fakes for resource service and auth service
        Http::fake([
            'http://resource_service/api/resources/1' => Http::response([
                'id' => 1,
                'name' => 'Conference Room',
                'assigned_admin_ids' => [101, 102],
                'assigned_admin_id' => 101,
            ], 200),
            'http://auth_service/api/internal/users/101' => Http::response([
                'id' => 101,
                'email' => 'admin1@test.com',
            ], 200),
            'http://auth_service/api/internal/users/102' => Http::response([
                'id' => 102,
                'email' => 'admin2@test.com',
            ], 200),
        ]);

        // Mock Mail to prevent sending real emails
        \Illuminate\Support\Facades\Mail::fake();

        // 2. Create a booking in the local test DB
        $booking = Booking::create([
            'user_id' => 999,
            'user_email' => 'guest@test.com',
            'user_type' => 'internal',
            'phone' => '0771234567',
            'booking_reference' => 'BK-101',
            'booking_date' => '2026-05-18',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'Pending',
            'confirmed_by_admins' => [],
        ]);

        BookingDetail::create([
            'booking_id' => $booking->id,
            'item_id' => 1,
            'item_type' => 'resource',
            'item_name' => 'Conference Room',
            'quantity' => 1,
            'price_per_hour' => 100.00,
            'hours' => 1,
            'subtotal' => 100.00,
        ]);

        // 3. Admin 101 tries to confirm the booking
        $response1 = $this->withHeaders([
            'X-User-Id' => '101',
            'X-User-Role' => 'Admin',
        ])->json('PATCH', "/api/bookings/{$booking->id}/status", [
            'status' => 'Confirmed'
        ]);

        $response1->assertStatus(200);
        $response1->assertJsonFragment([
            'message' => 'Your confirmation has been recorded. Waiting for other assigned admins to confirm.'
        ]);

        // Booking status should still be Pending
        $booking = $booking->fresh();
        $this->assertEquals('Pending', $booking->status);
        $this->assertContains(101, $booking->confirmed_by_admins);

        // 4. Admin 102 (not confirmed yet) tries to confirm the booking
        $response2 = $this->withHeaders([
            'X-User-Id' => '102',
            'X-User-Role' => 'Admin',
        ])->json('PATCH', "/api/bookings/{$booking->id}/status", [
            'status' => 'Confirmed'
        ]);

        $response2->assertStatus(200);
        
        // Booking should now be Confirmed because both admins confirmed
        $booking = $booking->fresh();
        $this->assertEquals('Confirmed', $booking->status);
        $this->assertContains(101, $booking->confirmed_by_admins);
        $this->assertContains(102, $booking->confirmed_by_admins);

        // 5. Master Admin bypass test
        // Let's create a new pending booking
        $booking2 = Booking::create([
            'user_id' => 999,
            'user_email' => 'guest2@test.com',
            'user_type' => 'internal',
            'phone' => '0771234567',
            'booking_reference' => 'BK-102',
            'booking_date' => '2026-05-18',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'Pending',
            'confirmed_by_admins' => [],
        ]);

        BookingDetail::create([
            'booking_id' => $booking2->id,
            'item_id' => 1,
            'item_type' => 'resource',
            'item_name' => 'Conference Room',
            'quantity' => 1,
            'price_per_hour' => 100.00,
            'hours' => 1,
            'subtotal' => 100.00,
        ]);

        $responseMaster = $this->withHeaders([
            'X-User-Id' => '200',
            'X-User-Role' => 'Master Admin',
        ])->json('PATCH', "/api/bookings/{$booking2->id}/status", [
            'status' => 'Confirmed'
        ]);

        $responseMaster->assertStatus(200);
        $responseMaster->assertJsonFragment([
            'message' => 'Booking confirmed successfully by Master Admin.'
        ]);

        $booking2 = $booking2->fresh();
        $this->assertEquals('Confirmed', $booking2->status);
    }
}
