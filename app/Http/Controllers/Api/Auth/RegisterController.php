<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationOtpMail;
use App\Models\EmailVerificationOtp;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

        // CREATE USER
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),

            'role' => 'admin',

            // FREE TRIAL
            'plan' => 'free_trial',
            'plan_duration' => '3_days',
            'plan_start' => $now,
            'plan_end' => $now->copy()->addDays(3),

            'is_activated' => true,
            'activated_at' => $now,
        ]);

        // MAKE USER THEIR OWN OWNER
        $user->owner_id = $user->id;
        $user->save();

        // =====================================================
        // 📧 CREATE EMAIL VERIFICATION OTP
        // =====================================================

        // Generate 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        // Store hashed OTP in database
        EmailVerificationOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(10),
            'attempts' => 0,
        ]);

        // Send OTP to user's email
        Mail::to($user->email)->send(
            new EmailVerificationOtpMail($user, $otp)
        );

        // =====================================================
        // 🔥 CREATE TOKEN FOR FLUTTER
        // =====================================================

        $token = $user->createToken('mobile_app_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Account created successfully. A verification code has been sent to your email.',
            'token' => $token,
            'user' => $user,
            'email_verified' => false,
        ], 201);
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();

        // Already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => true,
                'message' => 'Email already verified.',
                'email_verified' => true,
            ]);
        }

        // =====================================================
        // ⏱️ 60-SECOND RESEND COOLDOWN
        // =====================================================

        $recentOtp = EmailVerificationOtp::where('user_id', $user->id)
            ->where(
                'created_at',
                '>=',
                Carbon::now()->subSeconds(60)
            )
            ->latest()
            ->first();

        if ($recentOtp) {
            $secondsRemaining =
                60 - Carbon::now()->diffInSeconds($recentOtp->created_at);

            return response()->json([
                'status' => false,
                'message' => "Please wait {$secondsRemaining} seconds before requesting another code.",
            ], 429);
        }

        // Delete old OTP
        EmailVerificationOtp::where('user_id', $user->id)->delete();

        // Generate new 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        // Store hashed OTP
        EmailVerificationOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(10),
            'attempts' => 0,
        ]);

        // Send new OTP
        Mail::to($user->email)->send(
            new EmailVerificationOtpMail($user, $otp)
        );

        return response()->json([
            'status' => true,
            'message' => 'A new verification code has been sent to your email.',
            'email_verified' => false,
        ]);
    }
}
