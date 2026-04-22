<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyProductKey
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // If not logged in
        if (!$user) {
            return redirect('/login');
        }

        // Superadmin bypass
        if ($user->role === 'superadmin') {
            return $next($request);
        }

        // ❌ NOT ACTIVATED → force product key page
        if (!$user->is_activated) {
            return redirect('/product-key');
        }

        // ⛔ OPTIONAL: expiry check (VERY IMPORTANT)
        if ($user->plan_end && now()->greaterThan($user->plan_end)) {

            // deactivate account automatically
            $user->is_activated = false;
            $user->save();

            return redirect('/product-key')
                ->with('error', 'Your subscription has expired. Please renew.');
        }

        return $next($request);
    }
}