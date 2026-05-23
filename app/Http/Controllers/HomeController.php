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

    // activation check (optional but fine here)
    $owner = $user->owner_id
        ? \App\Models\User::find($user->owner_id)
        : $user;

    if (!$owner->is_activated) {
        return redirect('/show-product-key');
    }

    // 🔥 ROLE ROUTING (THIS IS THE KEY FIX)
    if ($user->role === 'superadmin') {
        return redirect('/superadmin-dashboard');
    }

    if ($user->role === 'admin') {
        return redirect('/admin-dashboard');
    }

    if ($user->role === 'manager') {
        return redirect('/manager-dashboard');
    }

    // cashier default
    return view('home');
}
}