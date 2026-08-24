<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ResourceAvailability;

class ResourceAvailabilitySlots extends Model
{
    // Use HasFactory trait
    use HasFactory;
    protected $table = 'resource_availability_slots';
    protected $fillable = [
        'resource_availability_id',
        'start_time',
        'end_time',
    ];

    // Define relationship with ResourceAvailability model
    public function availability(): BelongsTo
    {
        return $this->belongsTo(ResourceAvailability::class, 'resource_availability_id');
    }  
}
