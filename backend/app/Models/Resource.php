<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Resource extends Model
{
    // Define fillable fields
    protected $fillable = [
        'name',
        'location_name',
        'department',
        'description',
        'base_price',
        'category_id',
        'assigned_admin_id',
        'assigned_admin_ids',
        'template_data',
        'template_id',
        'status',
    ];

    // Cast template_data to array
    protected $casts = [
        'template_data' => 'array',
        'assigned_admin_ids' => 'array',
    ];

    // Define relationship with Category model
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Define relationship with ResourceImage model
    public function images()
    {
        return $this->hasMany(ResourceImage::class, 'resource_id')->orderBy('order_index');
    }

    // Accessor for featured image and image URLs
    public function getFeaturedImageAttribute()
    {
        return $this->images()->first();
    }

    // Accessor for image URLs
    public function getImageUrlsAttribute()
    {
        return $this->images->pluck('image_url');
    }

    // Define relationship with ResourceEquipment model
    public function equipment(): HasMany
    {
        return $this->hasMany(ResourceEquipment::class);
    }

    // Define relationship with ResourceAvailability model
    public function availability(): HasMany
    {
        return $this->hasMany(ResourceAvailability::class)->orderBy('day_of_week');
    }

    // Define relationship with ResourceTemplate model
    public function template(): BelongsTo
    {
        return $this->belongsTo(ResourceTemplate::class, 'template_id');
    }
}