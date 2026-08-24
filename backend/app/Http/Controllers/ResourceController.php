<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\ResourceImage;
use App\Models\ResourceEquipment;
use App\Models\ResourceAvailability;
use App\Models\ResourceAvailabilitySlots;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\BookingItem;
use App\Models\ItemStockLog;
use Exception;


class ResourceController extends Controller
{
    // List all resources with related data
    public function index(): JsonResponse
    {
        $resources = Cache::remember('all_resources', 60 * 60, function () {
            return Resource::with(['category', 'images', 'equipment', 'availability.slots'])->get();
        });
        return response()->json($resources);
    }

    // Show a specific resource with related data
    public function show($id): JsonResponse
    {
        $resource = Resource::with(['category', 'images', 'equipment', 'availability.slots'])->findOrFail($id);
        return response()->json($resource);
    }

    // Create a new resource with multiple time slots
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $validatedData = $this->validateResource($request);
        
        // Use transaction to ensure data integrity
        DB::beginTransaction();
        try {
            $resourceData = collect($validatedData)->except(['images', 'equipment', 'availability'])->toArray();
            
            if (isset($resourceData['assigned_admin_ids'])) {
                if (!empty($resourceData['assigned_admin_ids'])) {
                    $adminIds = array_map('intval', $resourceData['assigned_admin_ids']);
                    $resourceData['assigned_admin_ids'] = $adminIds;
                    $resourceData['assigned_admin_id'] = $adminIds[0];
                } else {
                    $resourceData['assigned_admin_ids'] = [];
                    $resourceData['assigned_admin_id'] = null;
                }
            }
            
            $resource = Resource::create($resourceData);

            // Handle Image Uploads
            if ($request->hasFile('images')) {
                $this->processImages($resource, $request->file('images'));
            }

            // Handle Equipment
            if (!empty($validatedData['equipment'])) {
                $resource->equipment()->createMany($validatedData['equipment']);
            }

            // Handle Availability and Slots
            if (!empty($validatedData['availability'])) {
                $this->syncAvailability($resource, $validatedData['availability']);
            }

            DB::commit();

            Cache::forget('all_resources');

            return response()->json([
                'message' => 'Resource created successfully',
                'resource' => $resource->load(['category', 'images', 'equipment', 'availability.slots'])
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Creation failed', 'error' => $e->getMessage()], 500);
        }
    }

    // Update an existing resource with multiple time slots
    public function update(Request $request, $id): JsonResponse
{
    // 1. Find the resource or fail early
    $resource = Resource::findOrFail($id);
    
    // 2. Validate input using your custom method
    $validatedData = $this->validateResource($request, true);

    DB::beginTransaction();
    try {
        // 3. Update base resource data
        // We exclude relationship keys to prevent SQL errors in the main table update
        $resourceData = collect($validatedData)->except([
            'images', 'equipment', 'availability', 'removeImages', 'delete_equipment'
        ])->toArray();

        if (isset($resourceData['assigned_admin_ids'])) {
            if (!empty($resourceData['assigned_admin_ids'])) {
                $adminIds = array_map('intval', $resourceData['assigned_admin_ids']);
                $resourceData['assigned_admin_ids'] = $adminIds;
                $resourceData['assigned_admin_id'] = $adminIds[0];
            } else {
                $resourceData['assigned_admin_ids'] = [];
                $resourceData['assigned_admin_id'] = null;
            }
        }

        $resource->update($resourceData);

        // 4. Handle Image Deletions
        if (!empty($request->removeImages)) {
            $images = ResourceImage::where('resource_id', $id)
                ->whereIn('id', $request->removeImages)
                ->get();
                
            foreach ($images as $img) {
                Storage::disk('public')->delete($img->file_path);
                $img->delete();
            }
        }

        // 5. Handle new image uploads
        if ($request->hasFile('images')) {
            $this->processImages($resource, $request->file('images'));
        }

        // 6. Sync Equipment (Fixed missing brace logic)
        if (isset($validatedData['equipment'])) {
            // Delete old equipment records and replace with new ones (Atomic Sync)
            $resource->equipment()->delete(); 
            
            foreach ($validatedData['equipment'] as $item) {
                $resource->equipment()->create([
                    'equipment_name' => $item['equipment_name'],
                    'quantity' => $item['quantity'],
                ]);
            }
        } // <--- Added the missing closing brace here

        // 7. Sync Availability and Slots
        if (isset($validatedData['availability'])) {
            $this->syncAvailability($resource, $validatedData['availability']);
        }

        DB::commit();

        Cache::forget('all_resources');

        // 8. Return the fully loaded resource for the Global Store
        return response()->json([
            'message' => 'Resource updated successfully',
            'resource' => $resource->load(['category', 'images', 'equipment', 'availability.slots'])
        ]);

    } catch (Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Update failed', 
            'error' => $e->getMessage()
        ], 500);
    }
}

    // Sync Resource Availability and Slots
    private function syncAvailability(Resource $resource, array $availabilityData)
    {
        foreach ($availabilityData as $data) {
            // Use 'day_of_week' as the name if 'day_name' is missing from the request
            $dayName = $data['day_name'] ?? $data['day_of_week']; 
            
            $availability = $resource->availability()->updateOrCreate(
                ['day_name' => $dayName],
                [
                    'day_of_week' => ResourceAvailability::getDayNumber($dayName),
                    'is_available' => filter_var($data['is_available'], FILTER_VALIDATE_BOOLEAN),
                    'day_name' => $dayName
                ]
            );

            $availability->slots()->delete();
            // Add new slots if available
            if ($availability->is_available && !empty($data['slots'])) {
                foreach ($data['slots'] as $slot) {
                    $availability->slots()->create([
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                    ]);
                }
            }
        }
    }
    
    // Validate resource input data
    private function validateResource(Request $request, $isUpdate = false)
    {
        // Validation rules
        return $request->validate([
            'name' => ($isUpdate ? 'sometimes' : 'required') . '|string|max:255',
            'location_name' => ($isUpdate ? 'sometimes' : 'required') . '|string',
            'department' => 'nullable|string',
            'category_id' => ($isUpdate ? 'sometimes' : 'required') . '|exists:categories,id',
            'base_price' => ($isUpdate ? 'sometimes' : 'required') . '|numeric|min:0',
            'status' => ($isUpdate ? 'sometimes' : 'required') . '|in:Active,Inactive,Maintenance',
            'description' => 'nullable|string',
            'assigned_admin_id' => 'nullable|integer',
            'assigned_admin_ids' => 'nullable|array',
            'assigned_admin_ids.*' => 'integer',
            'template_id' => 'nullable|integer|exists:resource_templates,id',
            'template_data' => 'nullable',
            'availability' => 'nullable|array',
            'availability.*.day_of_week' => 'required_with:availability|string',
            'availability.*.is_available' => 'required_with:availability',
            'availability.*.slots' => 'present|array',
            'availability.*.slots.*.start_time' => 'required|date_format:H:i',
            'availability.*.slots.*.end_time' => 'required|date_format:H:i|after:availability.*.slots.*.start_time',
            'equipment' => 'nullable|array',
            'equipment.*.equipment_name' => 'required_with:equipment|string',
            'equipment.*.quantity' => 'required_with:equipment|integer',
        ]);
    }

    // Process and store uploaded images
    private function processImages(Resource $resource, array $images)
    {
        // Enforce maximum of 10 images
        if (($resource->images()->count() + count($images)) > 10) {
            throw new Exception("Maximum 10 images allowed.");
        }

        foreach ($images as $image) {
            $path = $image->store('resource_images/' . $resource->id, 'public');
            $resource->images()->create([
                'file_path' => $path,
                'order_index' => $resource->images()->max('order_index') + 1,
                'alt_text' => $resource->name
            ]);
        }
    }

    // Delete a resource and its associated images
    public function destroy($id): JsonResponse
    {
        $resource = Resource::with('images')->findOrFail($id);
        foreach ($resource->images as $image) {
            Storage::disk('public')->delete($image->file_path);
        }

        $resource->delete();

        Cache::forget('all_resources');

        return response()->json(['message' => 'Deleted successfully']);
    }

    // Get resources in batch by IDs
    public function getBatch(Request $request): JsonResponse
    {
        $idsString = $request->query('ids');
        if (!$idsString) return response()->json(['message' => 'No IDs provided'], 400);
        $ids = explode(',', $idsString);
        $resources = Resource::with(['category', 'images', 'equipment', 'availability.slots'])->whereIn('id', $ids)->get();
        return response()->json($resources);
    }

    /**
     * Reserve stock for an item.
     */
    public function reserve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:booking_items,id',
            'booking_id' => 'required|integer',
            'date' => 'required|date',
            'start_time' => 'required', // Allow flexible format
            'end_time' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        // Normalize times to H:i
        $startTime = \Carbon\Carbon::parse($validated['start_time'])->format('H:i');
        $endTime = \Carbon\Carbon::parse($validated['end_time'])->format('H:i');

        return DB::transaction(function () use ($validated, $startTime, $endTime) {
            $item = BookingItem::findOrFail($validated['item_id']);
            $totalStock = $item->available_quantity;

            // Find all logs for this item on this date that overlap with the requested window
            // EXCLUDE the current booking itself to avoid double-counting
            $overlappingLogs = ItemStockLog::where('item_id', $validated['item_id'])
                ->where('date', $validated['date'])
                ->where('booking_id', '!=', $validated['booking_id'])
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                })
                ->get();

            // Calculate peak usage within the window
            $timePoints = $overlappingLogs->pluck('start_time')
                ->merge([$startTime])
                ->unique()
                ->filter(fn($t) => $t >= $startTime && $t < $endTime);
            
            $maxUsage = 0;
            foreach ($timePoints as $time) {
                // Peak usage = Sum of OTHER bookings' quantities + our new quantity
                $usageAtTime = $overlappingLogs->filter(function ($log) use ($time) {
                    return $time >= $log->start_time && $time < $log->end_time;
                })->sum('quantity') + $validated['quantity'];

                if ($usageAtTime > $maxUsage) {
                    $maxUsage = $usageAtTime;
                }
            }
            
            // Fallback if no overlapping logs
            if ($maxUsage === 0) {
                 $maxUsage = $validated['quantity'];
            }

            if ($maxUsage > $totalStock) {
                return response()->json(['message' => 'Out of Stock'], 422);
            }

            // DELETE any existing log for this booking/item pair (Idempotency)
            ItemStockLog::where('booking_id', $validated['booking_id'])
                        ->where('item_id', $validated['item_id'])
                        ->delete();

            ItemStockLog::create($validated);

            return response()->json(['message' => 'Success'], 201);
        });
    }

    /**
     * Release stock for a booking.
     */
    public function release(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer',
        ]);

        ItemStockLog::where('booking_id', $validated['booking_id'])->delete();

        return response()->json(['message' => 'Success']);
    }
}