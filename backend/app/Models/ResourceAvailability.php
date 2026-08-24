<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceAvailability extends Model
{
    protected $table = 'resource_availabilities';
    use HasFactory;

    // These fields must match your migration
    protected $fillable = [
        'resource_id',
        'day_of_week',
        'day_name',
        'is_available',
    ];

    // Cast fields to appropriate data types
    protected $casts = [
        'is_available' => 'boolean',
        'day_of_week' => 'integer',
    ];

    // Map day names to numbers
    public static $dayMap = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 7,
    ];

    // Define relationship with Resource model
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    // Static methods to get day number and name
    public static function getDayNumber(string $dayName): ?int
    {
        return self::$dayMap[$dayName] ?? 1;
    }

    // Static methods to get day number and name
    public static function getDayName(int $dayNumber): ?string
    {
        return array_search($dayNumber, self::$dayMap) ?: 'Monday';
    }

    // Define relationship with ResourceAvailabilitySlots model
    public function slots(): HasMany
    {
        return $this->hasMany(ResourceAvailabilitySlots::class, 'resource_availability_id');
    }

};
