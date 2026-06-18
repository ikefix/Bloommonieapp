<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class GoogleLoginController extends Controller
{
    public function googleLogin(Request $request)
    {
        // basic validation
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string'
        ]);

        // check if user exists
        $user = User::where('email', $request->email)->first();

        $now = Carbon::now();

        if (!$user) {
            // CREATE NEW USER
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,

                // random password (not used)
                'password' => Hash::make(uniqid()),

                'role' => 'admin',

                // BUSINESS PLAN (1 YEAR)
                'plan' => 'business',
                'plan_duration' => '1_year',
                'plan_start' => $now,
                'plan_end' => $now->copy()->addYear(),

                'is_activated' => true,
                'activated_at' => $now,
            ]);

            $user->owner_id = $user->id;
            $user->save();
        }

        // CREATE TOKEN (LOGIN)
        $token = $user->createToken('google_login_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Google login successful',
            'token' => $token,
            'user' => $user
        ]);
    }
}