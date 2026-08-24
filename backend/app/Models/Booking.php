<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    // Use HasFactory trait
    use HasFactory;
    protected $fillable = [
        'user_id',
        'user_email',
        'user_type',
        'phone',
        'is_verified',
        'otp_code',
        'otp_expires_at',
        'booking_reference',
        'booking_date',
        'start_time',
        'end_time',
        'total_amount',
        'status',
        'notes',
        'confirmed_by_admins',
    ];

    // Cast attributes
    protected $casts = [
        'booking_date' => 'date',
        'total_amount' => 'decimal:2',
        'is_verified' => 'boolean',
        'otp_expires_at' => 'datetime',
        'confirmed_by_admins' => 'array',
    ];
    protected $hidden = [
        'otp_code',
    ];

    // Define relationship with BookingDetail model
    public function details(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }

    // Only resource bookings
    public function resources(): HasMany
    {
        return $this->hasMany(BookingDetail::class)->where('item_type', 'resource');
    }

    // Only booking items
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingDetail::class)->where('item_type', 'booking_item');
    }

    // Generate a unique booking reference
    public static function generateReference(): string
    {
        do {
            $reference = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        } while (self::where('booking_reference', $reference)->exists());

        return $reference;
    }

    //generate OTP code
    public static function generateOTP(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Determine user type based on the provided override (passed from Gateway).
     * Strictly relies on role-based identification from the Gateway.
     */
    public static function getUserType(string $email, ?string $overrideType = null): string
    {
        if ($overrideType) {
            return $overrideType;
        }

        // Default to external if no header is provided (e.g. direct service access)
        return 'external';
    }

    //check if OTP is valid
    public function isOTPValid(string $otp): bool
    {
        if ($this->otp_code !== $otp) {
            return false;
        }
        if ($this->otp_expires_at && $this->otp_expires_at->isPast()) {
            return false;
        }
        return true;
    }

    // Calculate total hours between start_time and end_time
    public function calculateHours(): float
    {
        $start = \Carbon\carbon::parse($this->start_time);
        $end = \Carbon\carbon::parse($this->end_time);
        return $end->diffInMinutes($start) / 60;

    }
}
