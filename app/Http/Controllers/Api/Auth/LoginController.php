<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not found'
        ], 404);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    $owner = $user->owner_id
        ? User::find($user->owner_id)
        : $user;

    if (!$owner->is_activated) {
        return response()->json([
            'status' => false,
            'message' => 'Account not activated'
        ], 403);
    }

    // 🔥 SUBSCRIPTION CHECK
    if ($user->role !== 'superadmin' && $owner->plan_end && now()->greaterThan($owner->plan_end)) {
        return response()->json([
            'status' => false,
            'subscription_expired' => true,
            'message' => 'Your plan has expired. Please renew on the website to continue.'
        ], 403);
    }

    $token = $user->createToken('mobile_login_token')->plainTextToken;

    return response()->json([
        'status' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user,
        'role' => $user->role,
        'owner_id' => $user->owner_id
    ]);
}

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}