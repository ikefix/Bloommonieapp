<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
       
        $user = Auth::user();

        if (!$user) {
            return redirect('/login');
        }

        // SUPERADMIN
        if ($user->role === 'superadmin') {
            return redirect('/superadmin-dashboard');
        }

        // OWNER CHECK
        $owner = $user->owner_id
            ? \App\Models\User::find($user->owner_id)
            : $user;

        // ACTIVATION CHECK
        if (!$owner->is_activated) {
            return redirect('/show-product-key');
        }

        // ROLE REDIRECT
        if ($user->role === 'admin') {
            return redirect('/admin-dashboard');
        }

        if ($user->role === 'manager') {
            return redirect('/manager-dashboard');
        }

        return redirect('/home'); 
    }
}