<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Notifications\BloommonieAppResetPasswordNotification;

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

    $broker = Password::broker();

    $user = $broker->getUser(
        $request->only('email')
    );

    // Always return a generic response.
    // This prevents revealing whether an email is registered.
    $successResponse = [
        'status' => true,
        'message' => 'If an account exists with that email, a password reset link has been sent.',
    ];

    if (!$user) {
        return response()->json($successResponse);
    }

    // Generate a Laravel password-reset token.
    $token = $broker->createToken($user);

    // Send the dedicated Flutter app notification.
    $user->notify(
        new BloommonieAppResetPasswordNotification($token)
    );

    return response()->json($successResponse);
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

    public function showAppResetLanding(Request $request, $token)
{
    $email = $request->query('email');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        abort(400, 'Invalid password reset link.');
    }

    return view('auth.app-password-reset', [
        'token' => $token,
        'email' => $email,
    ]);
}
}
