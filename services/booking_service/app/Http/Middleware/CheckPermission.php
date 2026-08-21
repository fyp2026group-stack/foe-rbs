<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        $userId = $request->header('X-User-Id');
        
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized. Missing User Context.'], 401);
        }

        // Fetch permissions from shared Redis cache
        $cacheKey = "user_permissions_{$userId}";
        $permissions = Cache::get($cacheKey);

        if ($permissions === null) {
            return response()->json(['message' => 'Permission cache expired or not found. Please log out and log in again.'], 403);
        }

        // Master Admin override check
        if (in_array('*', $permissions)) {
            return $next($request);
        }

        // Specific permission check
        if (!in_array($permission, $permissions)) {
            return response()->json([
                'message' => "Access Denied. You do not have the permission: {$permission}"
            ], 403);
        }

        return $next($request);
    }
}
