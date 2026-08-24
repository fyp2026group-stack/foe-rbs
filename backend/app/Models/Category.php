<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    // Define table name and fillable fields
    protected $table = 'categories';
    protected $fillable = [
        'name',
        'description'
    ];

    // Define relationship with Resource model
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}