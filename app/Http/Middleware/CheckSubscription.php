<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscription
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        // SUPERADMIN bypass
        if ($user->role === 'superadmin') {
            return $next($request);
        }

        // Allow users without plan_end (new users)
        if (!$user->plan_end) {
            return $next($request);
        }

        // CHECK EXPIRY
        if (now()->greaterThan($user->plan_end)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'subscription_expired' => true,
                    'message' => 'Your plan has expired. Please renew on the website to continue.',
                ], 403);
            }

            return redirect('/subscription-expired');
        }

        return $next($request);
    }
}