<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Redirect users after login based on their role.
     */


protected function redirectTo()
{
    $user = Auth::user();

    if (!$user) {
        return '/login';
    }

    // SUPERADMIN bypass
    if ($user->role === 'superadmin') {
        return '/superadmin-dashboard';
    }

    // 🔥 Resolve OWNER (this is the missing piece)
    $owner = $user->owner_id
        ? \App\Models\User::find($user->owner_id)
        : $user;

    // ❌ NOT ACTIVATED (CHECK OWNER, NOT USER)
    if (!$owner->is_activated) {
        return '/show-product-key';
    }

    // ✅ ACTIVATED → go by role
    if ($user->role === 'admin') {
        return '/admin-dashboard';
    }

    if ($user->role === 'manager') {
        return '/manager-dashboard';
    }

    return '/home'; // cashier
}

    /**
     * Override credentials method to include role validation during login.
     */
    protected function credentials(Request $request)
    {
        return [
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role, // Ensure role matches during login
        ];
    }

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
