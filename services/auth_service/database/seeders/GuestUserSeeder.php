<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class GuestUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('name', 'Guest')->first();

        // Create a default guest user record
        $user = User::firstOrCreate(
            ['email' => 'guest@foe-rbs.lk'],
            [
                'name' => 'Guest User',
                'password' => Hash::make('guest123'),
                'status' => 'active',
                'department' => 'External',
            ]
        );

        if (!$user->roles()->where('name', 'Guest')->exists() && $role) {
            $user->roles()->attach($role->id);
        }
    }
}
