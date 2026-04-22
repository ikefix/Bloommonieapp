<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductKeyController extends Controller
{
    // SHOW FORM
    public function show()
    {
        return view('auth.product-key');
    }

    // VERIFY KEY
    public function verify(Request $request)
    {
        $request->validate([
            'product_key' => 'required'
        ]);

        $user = Auth::user();

        if ($user->product_key === $request->product_key) {

            // activate permanently until expiry
            $user->is_activated = true;
            $user->activated_at = now();
            $user->save();

            if ($user->role === 'superadmin') {
                return redirect('/superadmin-dashboard');
            } elseif ($user->role === 'admin') {
                return redirect('/admin-dashboard');
            } elseif ($user->role === 'manager') {
                return redirect('/manager-dashboard');
            }

            return redirect('/home');
        }

        return back()->with('error', 'Invalid Product Key');
    }
}
