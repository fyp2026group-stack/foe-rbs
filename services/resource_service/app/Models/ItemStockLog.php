<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemStockLog extends Model
{
    use HasFactory;

    protected $table = 'item_stock_logs';

    protected $fillable = [
        'item_id',
        'booking_id',
        'date',
        'start_time',
        'end_time',
        'quantity',
    ];

    /**
     * Relationship with BookingItem.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class, 'item_id');
    }
}
