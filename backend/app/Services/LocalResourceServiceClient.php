<?php

namespace App\Services;

use App\Models\BookingItem;
use App\Models\ItemStockLog;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * In-process replacement for the former booking-to-resource/auth HTTP calls.
 */
class LocalResourceServiceClient
{
    public static function timeout(int $seconds): self
    {
        // Kept for compatibility with the old HTTP-client call sites.
        return new self();
    }

    public static function get(string $url): LocalServiceResponse
    {
        if (preg_match('#/resources/([0-9]+)$#', $url, $matches)) {
            $resource = Resource::with(['category', 'images', 'equipment', 'availability.slots'])->find($matches[1]);
            return new LocalServiceResponse($resource?->toArray(), $resource ? 200 : 404);
        }
        if (str_ends_with($url, '/resources')) {
            return new LocalServiceResponse(Resource::with(['category', 'images', 'equipment', 'availability.slots'])->get()->toArray());
        }
        if (preg_match('#/booking-items/([0-9]+)$#', $url, $matches)) {
            $item = BookingItem::find($matches[1]);
            return new LocalServiceResponse($item?->toArray(), $item ? 200 : 404);
        }
        if (preg_match('#/internal/users/([0-9]+)$#', $url, $matches)) {
            $user = User::find($matches[1]);
            return new LocalServiceResponse($user?->toArray(), $user ? 200 : 404);
        }
        return new LocalServiceResponse(['message' => 'Unknown local service route'], 404);
    }

    public static function post(string $url, array $data = []): LocalServiceResponse
    {
        if (str_ends_with($url, '/items/release')) {
            ItemStockLog::where('booking_id', $data['booking_id'] ?? null)->delete();
            return new LocalServiceResponse(['message' => 'Success'], 200);
        }
        if (!str_ends_with($url, '/items/reserve')) {
            return new LocalServiceResponse(['message' => 'Unknown local service route'], 404);
        }

        foreach (['item_id', 'booking_id', 'date', 'start_time', 'end_time', 'quantity'] as $key) {
            if (!array_key_exists($key, $data)) return new LocalServiceResponse(['message' => "Missing {$key}"], 422);
        }

        return DB::transaction(function () use ($data) {
            $item = BookingItem::find($data['item_id']);
            if (!$item) return new LocalServiceResponse(['message' => 'Item not found'], 404);
            $start = Carbon::parse($data['start_time'])->format('H:i');
            $end = Carbon::parse($data['end_time'])->format('H:i');
            $logs = ItemStockLog::where('item_id', $item->id)
                ->where('date', $data['date'])
                ->where('booking_id', '!=', $data['booking_id'])
                ->where(fn ($q) => $q->where('start_time', '<', $end)->where('end_time', '>', $start))
                ->get();
            $points = $logs->pluck('start_time')->merge([$start])->unique()->filter(fn ($time) => $time >= $start && $time < $end);
            $peak = max(0, ...$points->map(fn ($time) => $logs->filter(fn ($log) => $time >= $log->start_time && $time < $log->end_time)->sum('quantity') + $data['quantity'])->all());
            if ($peak > $item->available_quantity) return new LocalServiceResponse(['message' => 'Out of Stock'], 422);
            ItemStockLog::where('booking_id', $data['booking_id'])->where('item_id', $item->id)->delete();
            ItemStockLog::create([
                'item_id' => $item->id, 'booking_id' => $data['booking_id'], 'date' => $data['date'],
                'start_time' => $start, 'end_time' => $end, 'quantity' => $data['quantity'],
            ]);
            return new LocalServiceResponse(['message' => 'Success'], 201);
        });
    }
}
