<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    // update self profile
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (!$user)
            return response()->json(['message' => 'Unauthenticated'], 401);

        $validated = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'department' => 'nullable|string',
        ]);

        $dataToUpdate = $validated;

        if (isset($dataToUpdate['password']) && !empty($dataToUpdate['password'])) {
            $dataToUpdate['password'] = Hash::make($dataToUpdate['password']);
        } else {
            unset($dataToUpdate['password']);
        }

        $user->update($dataToUpdate);
        return response()->json($user->load('roles'));
    }

    // index
    public function index()
    {
        return User::with('roles')->get();
    }

    // store
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'department' => 'nullable|string',
        ]);
        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department' => $validated['department'],
            'status' => 'active',
        ]);
        // Assign default role 'User'
        $role = Role::where('name', 'User')->first();
        if ($role) {
            $user->roles()->attach($role);
        }

        return response()->json($user, 201);
    }

    // update
    public function update(Request $request, User $user)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'status' => 'nullable|in:active,inactive',
            'role' => 'nullable|string|exists:roles,name',
        ]);
        $dataToUpdate = $validated;
        // Hash password if provided
        if (isset($dataToUpdate['password'])) {
            $dataToUpdate['password'] = Hash::make($dataToUpdate['password']);
        }
        // Update status if provided (handled in dataToUpdate implicitly if passed, but explicit is fine)
        if (isset($validated['role'])) {
            unset($dataToUpdate['role']);
        }

        // Apply all updates except role
        $user->update($dataToUpdate);

        // Update role if provided
        if (isset($validated['role'])) {
            if (!$request->user()->tokenCan('*')) {
                return response()->json(['message' => 'Forbidden. Only Master Admin can update user roles.'], 403);
            }
            $role = Role::where('name', $validated['role'])->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }
        }


        Cache::put("user_permissions_{$user->id}", $user->getAllPermissions(), now()->addHours(24));

        return response()->json($user->load('roles'));
    }

    //get all admins
    public function getAdmins()
    {
        // Fetch users with 'Admin' role
        $adminRole = Role::where('name', 'Admin')->first();
        if (!$adminRole) {
            return response()->json([], 200);
        }
        $admins = User::whereHas('roles', function ($query) use ($adminRole) {
            $query->where('role_id', $adminRole->id);
        })->with('roles')->get();
        return response()->json($admins, 200);
    }

    // destroy
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
