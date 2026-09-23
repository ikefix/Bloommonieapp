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
                'plan' => 'free_trial',
                'plan_duration' => '3_days',
                'plan_start' => $now,
                'plan_end' => $now->copy()->addDays(3),

                'is_activated' => true,
                'activated_at' => $now,

                // Google already verifies the email on their end
                'email_verified_at' => $now,
            ]);

            $user->owner_id = $user->id;
            $user->save();
        }

        $owner = $user->owner_id
            ? User::find($user->owner_id)
            : $user;

        $daysLeft = $owner->plan_end
            ? now()->diffInDays($owner->plan_end, false)
            : 0;

        // CREATE TOKEN (LOGIN)
        $token = $user->createToken('google_login_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Google login successful',
            'token' => $token,
            'user' => $user,
            'role' => $user->role,
            'owner_id' => $user->owner_id,
            'plan' => $owner->plan,
            'is_free_trial' => $owner->plan === 'free_trial',
            'trial_days_left' => $daysLeft,
            'email_verified' => $user->email_verified_at !== null,
        ]);
    }
}