<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRestricted
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->is_restricted) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'restricted' => true,
                    'message' => 'Your account has been restricted. Please contact your admin.',
                ], 403);
            }

            auth()->logout();
            return redirect('/login')->with('error', 'Your account has been restricted.');
        }

        return $next($request);
    }
}