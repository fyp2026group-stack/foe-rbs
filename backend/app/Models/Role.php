<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Role extends Model
{
    // Use HasFactory trait
    protected $fillable = [
        'name'
    ];

    // Define many-to-many relationship with User model
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

}
