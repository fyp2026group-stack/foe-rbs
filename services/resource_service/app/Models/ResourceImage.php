<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Resource;
use Illuminate\Support\Facades\Storage;

class ResourceImage extends Model
{
    // Use HasFactory trait
    use HasFactory;
    protected $fillable = [
        'resource_id',
        'file_path',
        'order_index',
        'alt_text',
    ];
    protected $appends = ['image_url'];

    // Define relationship with Resource model
    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    // Accessor for image URL
    public function getImageUrlAttribute()
    {
        if ($this->file_path) {
            return 'http://localhost:8000/api/resources/storage/' . $this->file_path;
        }
        return null;
    }

    // Boot method to handle deletion of image file
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            if (\Storage::disk('public')->exists($image->file_path)) {
                \Storage::disk('public')->delete($image->file_path);
            }
        });
    }
}
