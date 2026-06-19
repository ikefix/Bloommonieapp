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
        // validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // find user
        $user = User::where('email', $request->email)->first();

        // check user exists
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        // check password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // resolve owner (your logic kept)
        $owner = $user->owner_id
            ? User::find($user->owner_id)
            : $user;

        // check activation
        if (!$owner->is_activated) {
            return response()->json([
                'status' => false,
                'message' => 'Account not activated'
            ], 403);
        }

        // create token
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