<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationOtpMail;
use App\Models\EmailVerificationOtp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    /**
     * Show OTP verification page.
     */
    public function show()
    {
        return view('auth.verify-email');
    }

    /**
     * Verify OTP from web.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect('/admin-dashboard');
        }

        $otpRecord = EmailVerificationOtp::where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$otpRecord) {
            return back()->withErrors([
                'otp' => 'No verification code found. Please request a new code.',
            ]);
        }

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {

            $otpRecord->delete();

            return back()->withErrors([
                'otp' => 'This verification code has expired. Please request a new code.',
            ]);
        }

        if ($otpRecord->attempts >= 5) {

            $otpRecord->delete();

            return back()->withErrors([
                'otp' => 'Too many incorrect attempts. Please request a new code.',
            ]);
        }

        if (!Hash::check($request->otp, $otpRecord->otp_hash)) {

            $otpRecord->increment('attempts');

            return back()->withErrors([
                'otp' => 'Invalid verification code.',
            ]);
        }

        // MARK USER AS VERIFIED
        $user->email_verified_at = Carbon::now();
        $user->save();

        // Delete used OTP
        $otpRecord->delete();

        return redirect('/admin-dashboard')
            ->with('success', 'Your email has been verified successfully.');
    }

    /**
     * Resend OTP.
     */
    public function resendOtp(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect('/admin-dashboard');
        }

        // Prevent resend spam
        $recentOtp = EmailVerificationOtp::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subSeconds(60))
            ->latest()
            ->first();

        if ($recentOtp) {

            $secondsRemaining =
                60 - Carbon::now()->diffInSeconds($recentOtp->created_at);

            return back()->with('error',
                "Please wait {$secondsRemaining} seconds before requesting another code."
            );
        }

        // Remove old OTP
        EmailVerificationOtp::where('user_id', $user->id)->delete();

        // Generate new OTP
        $otp = (string) random_int(100000, 999999);

        // Store hashed OTP
        EmailVerificationOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(10),
            'attempts' => 0,
        ]);

        // Send email
        Mail::to($user->email)->send(
            new EmailVerificationOtpMail($user, $otp)
        );

        return back()->with(
            'success',
            'A new verification code has been sent to your email.'
        );
    }
}