<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationOtpMail;
use App\Models\EmailVerificationOtp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

use Carbon\Carbon;

class EmailVerificationController extends Controller
{
    /**
     * Verify email using OTP.
     */
    public function verifyOtp(Request $request)
    {
        // OTP only — user comes from Sanctum authentication
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        // Get authenticated user
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Already verified
        if ($user->email_verified_at) {
            return response()->json([
                'status' => true,
                'message' => 'Email is already verified.',
                'email_verified' => true,
            ]);
        }

        // Get latest OTP belonging to authenticated user
        $otpRecord = EmailVerificationOtp::where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status' => false,
                'message' => 'No verification code found. Please request a new code.',
            ], 404);
        }

        // Check expiry
        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            $otpRecord->delete();

            return response()->json([
                'status' => false,
                'message' => 'This verification code has expired. Please request a new code.',
            ], 422);
        }

        // Check attempts
        if ($otpRecord->attempts >= 5) {
            $otpRecord->delete();

            return response()->json([
                'status' => false,
                'message' => 'Too many incorrect attempts. Please request a new code.',
            ], 429);
        }

        // Check OTP
        if (!Hash::check($request->otp, $otpRecord->otp_hash)) {

            $otpRecord->increment('attempts');

            return response()->json([
                'status' => false,
                'message' => 'Invalid verification code.',
            ], 422);
        }

        // =====================================================
        // SUCCESS
        // =====================================================

        $user->email_verified_at = Carbon::now();
        $user->save();

        // Delete used OTP
        $otpRecord->delete();

        return response()->json([
            'status' => true,
            'message' => 'Email verified successfully.',
            'email_verified' => true,
            'user' => $user,
        ]);
    }


    /**
     * Resend verification OTP.
     */
    public function resendOtp(Request $request)
    {
        // Get authenticated user
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Already verified
        if ($user->email_verified_at) {
            return response()->json([
                'status' => true,
                'message' => 'Email is already verified.',
                'email_verified' => true,
            ]);
        }

        // =====================================================
        // 60-SECOND RESEND COOLDOWN
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

        // Remove previous OTPs
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

        // Send OTP email
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

