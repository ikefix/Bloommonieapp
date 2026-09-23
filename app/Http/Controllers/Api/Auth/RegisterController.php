<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use Carbon\Carbon;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $now = Carbon::now();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),

            'role' => 'admin',

            // BUSINESS PLAN (1 YEAR)
            'plan' => 'free_trial',
            'plan_duration' => '3_days',
            'plan_start' => $now,
            'plan_end' => $now->copy()->addDays(3),

            'is_activated' => true,
            'activated_at' => $now,
        ]);

        $user->owner_id = $user->id;
        $user->save();

        // 📧 SEND EMAIL VERIFICATION
        $user->sendEmailVerificationNotification();

        // 🔥 CREATE TOKEN FOR FLUTTER
        $token = $user->createToken('mobile_app_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Account created successfully',
            'token' => $token,
            'user' => $user,
            'email_verified' => false,
        ], 201);
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => true,
                'message' => 'Email already verified.'
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'status' => true,
            'message' => 'Verification email sent.'
        ]);
    }
}