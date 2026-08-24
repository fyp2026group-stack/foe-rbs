<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    // Use necessary traits
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role',
        'department'
    ];

    // Hide sensitive attributes
    protected $hidden = [
        'password',
        'remember_token'
    ];

    // Cast attributes
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Define relationship with UserPermissionOverride
    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    // Define many-to-many relationship with Role model
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    // Get all permissions considering role defaults and user-specific overrides
    public function getAllPermissions(): array
    {
        // Identify if the user is a Master Admin (they have everything)
        // We check if the role exists in their roles collection
        if ($this->roles()->where('name', 'Master Admin')->exists()) {
            return ['*'];
        }

        // Define standard role defaults (Admins and Users start with no hardcoded permissions)
        // Master Admin is handled above. 
        $roleDefaults = [
            'Admin' => [
                'view_assigned_bookings',
                'manage_resources',
                'manage_bookings',
                'view_reports',
                'manage_categories',
                'manage_users'
            ],
            'User'  => [],
        ];

        // Identify the primary role name for defaults (if any)
        $roleName = $this->roles()->first()?->name;
        $permissions = $roleDefaults[$roleName] ?? [];

        // Apply User-Specific Overrides from database
        $overrides = $this->permissionOverrides()->get();
        foreach ($overrides as $override) {
            if ($override->is_allowed) {
                if (!in_array($override->permission_slug, $permissions)) {
                    $permissions[] = $override->permission_slug;
                }
            } else {
                $permissions = array_filter($permissions, fn($p) => $p !== $override->permission_slug);
            }
        }

        return array_values($permissions);
    }
}