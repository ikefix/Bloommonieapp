<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Send a password reset link to the user's email.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => true,
                'message' => 'If an account exists with that email, a password reset link has been sent.',
            ]);
        }

        if ($status === Password::INVALID_USER) {
            // Keep the response generic to avoid revealing registered emails.
            return response()->json([
                'status' => true,
                'message' => 'If an account exists with that email, a password reset link has been sent.',
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unable to send the reset link. Please try again later.',
        ], 500);
    }

    /**
     * Reset the user's password using the emailed token.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => true,
                'message' => 'Your password has been reset successfully. You can now log in.',
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => $status === Password::INVALID_TOKEN
                ? 'This password reset link is invalid or has expired. Please request another one.'
                : 'Unable to reset your password. Please try again.',
        ], 422);
    }
}
