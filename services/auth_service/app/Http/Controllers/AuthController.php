<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordOtpMail;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    // login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::with('roles')->where('email', $request->email)->first();

        if (!$user || !\Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Your account is inactive. Please contact an administrator.'], 403);
        }
        $permissions = $user->getAllPermissions();

        // Populate Redis cache for microservices
        Cache::put("user_permissions_{$user->id}", $permissions, now()->addHours(24));

        $roleNames = $user->roles->pluck('name');
        $token = $user->createToken('auth_token', $permissions)->plainTextToken;
        return response()->json([
            'user' => $user,
            'roles' => $roleNames,
            'token' => $token,
            'permissions' => $permissions
        ]);
    }

    // register
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'department' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department' => $validated['department'] ?? null,
            'status' => 'active',
        ]);

        // Assign role based on whether they belong to a department
        $isInternal = !empty($validated['department']);
        $roleName = $isInternal ? 'User' : 'Guest';
        $role = \App\Models\Role::where('name', $roleName)->first();
        if ($role) {
            $user->roles()->attach($role);
        }

        $permissions = $user->getAllPermissions();
        $token = $user->createToken('auth_token', $permissions)->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'roles' => $user->roles->pluck('name'),
            'token' => $token,
            'permissions' => $permissions
        ], 201);
    }

    // sendResetOtp
    public function sendResetOtp(Request $request): JsonResponse
    {
        // Validate email
        $request->validate(['email' => 'required|email|exists:users,email']);
        $email = $request->email;
        $token = strval(rand(100000, 999999));

        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );
        //Send the actual email
        Mail::to($email)->send(new ResetPasswordOtpMail($token));
        return response()->json([
            'message' => 'OTP sent successfully. Check your email.'
        ]);
    }


    // verifyOtp
    public function verifyOtp(Request $request): JsonResponse
    {
        // Validate input
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);
        // Retrieve the password reset record
        $resetRecord = DB::table('password_resets')
            ->where('email', $validated['email'])
            ->first();
        if (
            !$resetRecord ||
            !Hash::check($validated['otp'], $resetRecord->token) ||
            \Carbon\Carbon::parse($resetRecord->created_at)->addMinutes(15)->isPast()
        ) {
            return response()->json([
                'message' => 'Invalid or expired OTP.'
            ], 401);
        }
        return response()->json([
            'message' => 'OTP verified successfully. You can now set your new password.'
        ]);
    }

    // resetPassword
    public function resetPassword(Request $request): JsonResponse
    {
        // Validate input
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);
        // Retrieve the password reset record
        $resetRecord = DB::table('password_resets')
            ->where('email', $validated['email'])
            ->first();
        // Verify the OTP
        if (!$resetRecord || !Hash::check($validated['otp'], $resetRecord->token)) {
            return response()->json(['message' => 'Invalid verification token.'], 401);
        }
        // Update the user's password
        $user = User::where('email', $validated['email'])->first();
        $user->password = Hash::make($validated['password']);
        $user->save();
        DB::table('password_resets')->where('email', $validated['email'])->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }

    // guestLogin
    public function guestLogin(Request $request)
    {
        $guestEmail = 'guest@foe-rbs.lk';
        $user = User::with('roles')->where('email', $guestEmail)->first();

        if (!$user) {
            return response()->json(['message' => 'Guest user not found. Please run seeders.'], 404);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Guest access is currently disabled. Please contact an administrator.'], 403);
        }

        $permissions = $user->getAllPermissions();

        // Populate Redis cache for microservices
        Cache::put("user_permissions_{$user->id}", $permissions, now()->addHours(24));

        $roleNames = $user->roles->pluck('name');
        $token = $user->createToken('guest_token', $permissions)->plainTextToken;

        return response()->json([
            'user' => $user,
            'roles' => $roleNames,
            'token' => $token,
            'permissions' => $permissions
        ]);
    }

    // logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
