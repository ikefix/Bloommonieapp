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
        return view('superadmin.dashboard');
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
}