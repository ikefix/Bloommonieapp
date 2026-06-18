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
            'password' => Hash::make($request->password),

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

        // 🔥 CREATE TOKEN FOR FLUTTER
        $token = $user->createToken('mobile_app_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Account created successfully',
            'token' => $token,
            'user' => $user
        ], 201);
    }
}