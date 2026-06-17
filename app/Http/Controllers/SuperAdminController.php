<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminCreatedMail;

class SuperAdminController extends Controller
{
public function dashboard()
{
    $totalAdmins        = \App\Models\User::where('role', 'admin')->count();
    $activeAdmins       = \App\Models\User::where('role', 'admin')->where('status', 'active')->count();
    $inactiveAdmins     = \App\Models\User::where('role', 'admin')->where('status', 'inactive')->count();

    $subscribedAdmins   = \App\Models\User::where('role', 'admin')
                            ->whereHas('subscription', fn($q) => $q->where('status', 'active'))
                            ->count();

    $overdueAdmins      = \App\Models\User::where('role', 'admin')
                            ->whereHas('subscription', fn($q) => $q->where('status', 'overdue'))
                            ->count();

    $recentAdmins       = \App\Models\User::where('role', 'admin')
                            ->latest()
                            ->take(5)
                            ->get();

    $mostActiveAdmins   = \App\Models\User::where('role', 'admin')
                            ->withCount('productions')
                            ->orderByDesc('productions_count')
                            ->take(5)
                            ->get();

    $monthlySignups     = \App\Models\User::where('role', 'admin')
                            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                            ->whereYear('created_at', now()->year)
                            ->groupBy('month')
                            ->orderBy('month')
                            ->pluck('count', 'month');

    return view('superadmin.dashboard', compact(
        'totalAdmins', 'activeAdmins', 'inactiveAdmins',
        'subscribedAdmins', 'overdueAdmins',
        'recentAdmins', 'mostActiveAdmins', 'monthlySignups'
    ));
}

    public function create()
    {
        return view('superadmin.create-admin');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'plan' => 'required',
        ]);

        // 🔑 GENERATE PRODUCT KEY
        $productKey = strtoupper(Str::random(4)) . '-' .
                      strtoupper(Str::random(4)) . '-' .
                      strtoupper(Str::random(4));

        // 🧠 CREATE ADMIN
        $admin = new User();
        $admin->name = 'Admin';
        $admin->email = $request->email;
        $admin->password = Hash::make($request->password);
        $admin->role = 'admin';

        $admin->plan = $request->plan;
        $admin->plan_start = now();

        // handle plan end
        if ($request->plan === 'free_trial') {
            $admin->plan_end = $request->plan_end;
        } else {
            $admin->plan_duration = $request->plan_duration;
        }

        $admin->product_key = $productKey;

        $admin->save();

        // 🔥 owner system
        $admin->owner_id = $admin->id;
        $admin->save();

        // 📧 SEND EMAIL (simple inline)
        Mail::to($admin->email)->send(
            new AdminCreatedMail($admin, $request->password)
        );

        return back()->with('success', 'Admin created successfully');
    }

    public function subscriptions()
{
    $admins = User::where('role', 'admin')
        ->latest()
        ->get();

    return view('superadmin.subscriptions', compact('admins'));
}
}