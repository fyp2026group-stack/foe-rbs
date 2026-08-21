<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPermissionOverride;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserPermissionController extends Controller
{
    // List all user permission overrides
    public function index()
    {
        return response()->json(UserPermissionOverride::with('user')->get());
    }

    // Get effective permissions for a specific user
    public function getPermissions($userId)
    {
        try {
            $user = User::findOrFail($userId);
            // Invalidate and refresh cache for microservices
            $permissions = $user->getAllPermissions();
            Cache::put("user_permissions_{$userId}", $permissions, now()->addHours(24));

            return response()->json([
                'message' => 'Permissions retrieved and cache refreshed.',
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // Update user permissions with overrides
    public function updatePermissions(Request $request, $userId)
    {
        // Validate the simple format used in your Postman screenshot
        $validated = $request->validate([
            'permission_slug' => 'required|string',
            'is_allowed' => 'required|boolean'
        ]);

        // Update or Create the override
        UserPermissionOverride::updateOrCreate(
            ['user_id' => $userId, 'permission_slug' => $validated['permission_slug']],
            ['is_allowed' => $validated['is_allowed']]
        );

        Cache::put("user_permissions_{$userId}", User::find($userId)->getAllPermissions(), now()->addHours(24));

        return response()->json(['message' => 'User permission override updated successfully']);
    }
}
