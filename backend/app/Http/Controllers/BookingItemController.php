<?php

namespace App\Http\Controllers;

use App\Models\BookingItem;
use App\Models\ItemStockLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;


class BookingItemController extends Controller
{
    // List all booking items
    public function index(): JsonResponse
    {
        $items = BookingItem::orderBy('name')->get();
        return response()->json($items);
    }

    // Show a specific booking item
    public function show($id): JsonResponse
    {
        $item = BookingItem::findOrFail($id);
        return response()->json($item);
    }

    // Create a new booking item
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'item_code' => 'nullable|string|unique:booking_items,item_code',
            'description' => 'nullable|string',
            'price_per_hour' => 'required|numeric|min:0',
            'available_quantity' => 'required|integer|min:1',
            'status' => 'required|in:Available,Unavailable,Maintenance',
        ]);

        DB::beginTransaction();
        try {
            // Generate item code if not provided
            if (empty($validatedData['item_code'])) {
                $validatedData['item_code'] = BookingItem::generateItemCode();
            }

            $item = BookingItem::create($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Booking item created successfully',
                'item' => $item
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Booking item creation failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Booking item creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update an existing booking item
    public function update(Request $request, $id): JsonResponse
    {
        $item = BookingItem::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'item_code' => 'sometimes|string|unique:booking_items,item_code,' . $id,
            'description' => 'nullable|string',
            'price_per_hour' => 'sometimes|numeric|min:0',
            'available_quantity' => 'sometimes|integer|min:0',
            'assigned_admin_id' => 'nullable|integer',
            'status' => 'sometimes|in:Available,Unavailable,Maintenance',
        ]);

        DB::beginTransaction();
        try {
            // Auto-update status based on quantity
            if (isset($validatedData['available_quantity'])) {
                if ($validatedData['available_quantity'] == 0) {
                    // If quantity is 0, set status to Unavailable (unless it's in Maintenance)
                    if (!isset($validatedData['status']) || $validatedData['status'] !== 'Maintenance') {
                        $validatedData['status'] = 'Unavailable';
                    }
                } elseif ($validatedData['available_quantity'] > 0) {
                    // If quantity > 0 and status is Unavailable, set to Available
                    if ($item->status === 'Unavailable' && !isset($validatedData['status'])) {
                        $validatedData['status'] = 'Available';
                    }
                }
            }

            // If status is manually set to Available but quantity is 0, prevent it
            if (isset($validatedData['status']) && $validatedData['status'] === 'Available') {
                $quantity = $validatedData['available_quantity'] ?? $item->available_quantity;
                if ($quantity == 0) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Cannot set status to Available when quantity is 0',
                        'error' => 'Invalid status for zero quantity'
                    ], 422);
                }
            }

            $item->update($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Booking item updated successfully',
                'item' => $item
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Booking item update failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Booking item update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a booking item
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Find and delete the booking item
            $item = BookingItem::findOrFail($id);
            $item->delete();

            DB::commit();

            return response()->json([
                'message' => 'Booking item deleted successfully'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            
            \Log::error("Booking item deletion failed: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Booking item deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // List available booking items
    public function available(): JsonResponse
    {
        // Fetch items that are available and have quantity > 0
        $items = BookingItem::where('status', 'Available')
            ->where('available_quantity', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }

    /**
     * Get dynamic availability for items based on a date and time window.
     */
    public function availability(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'item_id' => 'nullable|exists:booking_items,id'
        ]);

        $date = $request->query('date');
        $startTime = \Carbon\Carbon::parse($request->query('start_time'))->format('H:i');
        $endTime = \Carbon\Carbon::parse($request->query('end_time'))->format('H:i');
        $itemId = $request->query('item_id');

        $query = BookingItem::where('status', 'Available');
        if ($itemId) {
            $query->where('id', $itemId);
        }

        $items = $query->get();

        $itemsWithAvailability = $items->map(function ($item) use ($date, $startTime, $endTime) {
            $totalStock = $item->available_quantity;

            // Find all logs for this item on this date that overlap with the requested window
            $overlappingLogs = ItemStockLog::where('item_id', $item->id)
                ->where('date', $date)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->get();

            // Calculate peak usage within the requested window
            $timePoints = $overlappingLogs->pluck('start_time')
                ->merge($overlappingLogs->pluck('end_time'))
                ->merge([$startTime, $endTime])
                ->unique()
                ->filter(fn($t) => $t >= $startTime && $t <= $endTime)
                ->sort();

            $maxReserved = 0;
            if ($timePoints->isEmpty()) {
                $maxReserved = 0;
            } else {
                foreach ($timePoints as $time) {
                    $usageAtTime = $overlappingLogs->filter(function ($log) use ($time) {
                        return $time >= $log->start_time && $time < $log->end_time;
                    })->sum('quantity');

                    if ($usageAtTime > $maxReserved) {
                        $maxReserved = $usageAtTime;
                    }
                }
            }

            $currentAvailable = max(0, $totalStock - $maxReserved);

            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price_per_hour' => $item->price_per_hour,
                'total_quantity' => $totalStock,
                'available_quantity' => $currentAvailable,
                'status' => $item->status,
            ];
        });

        return response()->json($itemsWithAvailability);
    }
}