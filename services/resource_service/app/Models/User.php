<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    // Use necessary traits
    use HasApiTokens, HasFactory, Notifiable;
    // Define fillable attributes
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // Hide sensitive attributes
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Cast attributes
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
