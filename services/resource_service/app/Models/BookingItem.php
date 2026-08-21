<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    // Use HasFactory trait
    use HasFactory;
    protected $fillable = [
        'name',
        'item_code',
        'description',
        'price_per_hour',
        'available_quantity',
        'status',
    ];

    // Cast attributes
    protected $casts = [
        'price_per_hour' => 'decimal:2',
        'available_quantity' => 'integer',
    ];

    // Generate a unique item code
    public static function generateItemCode(): string
    {
        do{
            $code = 'ITEM' . strtoupper(substr(uniqid(), -8));
        } while (self::where('item_code', $code)->exists());
        return $code;

    }
    
}
