<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class ResourceServiceClient
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('RESOURCE_SERVICE_URL', 'http://resource_service/api');
    }

    /**
     * Get resource by ID
     */
    public function getResource($id)
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/resources/{$id}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            \Log::warning("Resource {$id} not found or unavailable");
            return null;
        } catch (Exception $e) {
            \Log::error("Failed to fetch resource {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get booking item by ID
     */
    public function getBookingItem($id)
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/booking-items/{$id}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            \Log::warning("Booking item {$id} not found or unavailable");
            return null;
        } catch (Exception $e) {
            \Log::error("Failed to fetch booking item {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check resource availability
     */
    public function checkResourceAvailability($resourceId, $date, $startTime, $endTime)
    {
        try {
            $resource = $this->getResource($resourceId);
            
            if (!$resource) {
                return false;
            }

            // Check if resource is active
            if ($resource['status'] !== 'Active') {
                \Log::info("Resource {$resourceId} is not active. Status: {$resource['status']}");
                return false;
            }

            // Get day of week
            $dayOfWeek = \Carbon\Carbon::parse($date)->format('l'); // Monday, Tuesday, etc.
            
            // Check availability for this day
            $availability = collect($resource['availability'] ?? [])
                ->firstWhere('day_name', $dayOfWeek);

            if (!$availability) {
                \Log::info("No availability set for {$dayOfWeek} for resource {$resourceId}");
                return false;
            }

            if (!$availability['is_available']) {
                \Log::info("Resource {$resourceId} is not available on {$dayOfWeek}");
                return false;
            }

            // Check time range
            $requestStart = \Carbon\Carbon::parse($startTime);
            $requestEnd = \Carbon\Carbon::parse($endTime);
            $availableStart = \Carbon\Carbon::parse($availability['start_time']);
            $availableEnd = \Carbon\Carbon::parse($availability['end_time']);

            if ($requestStart->lt($availableStart) || $requestEnd->gt($availableEnd)) {
                \Log::info("Requested time ({$startTime}-{$endTime}) is outside available hours ({$availability['start_time']}-{$availability['end_time']})");
                return false;
            }

            return true;

        } catch (Exception $e) {
            \Log::error("Availability check failed for resource {$resourceId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check booking item availability (quantity based)
     */
    public function checkBookingItemAvailability($itemId, $quantity)
    {
        try {
            $item = $this->getBookingItem($itemId);
            
            if (!$item) {
                return false;
            }

            // Check if item is available
            if ($item['status'] !== 'Available') {
                \Log::info("Booking item {$itemId} is not available. Status: {$item['status']}");
                return false;
            }

            // Check quantity
            if ($item['available_quantity'] < $quantity) {
                \Log::info("Booking item {$itemId} doesn't have enough quantity. Available: {$item['available_quantity']}, Requested: {$quantity}");
                return false;
            }

            return true;

        } catch (Exception $e) {
            \Log::error("Booking item availability check failed for item {$itemId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all resources
     */
    public function getAllResources()
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/resources");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        } catch (Exception $e) {
            \Log::error("Failed to fetch all resources: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all available booking items
     */
    public function getAvailableBookingItems()
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/booking-items/available");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return [];
        } catch (Exception $e) {
            \Log::error("Failed to fetch available booking items: " . $e->getMessage());
            return [];
        }
    }
}