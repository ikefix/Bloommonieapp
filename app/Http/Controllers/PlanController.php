<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shop;
use App\Models\Product;
use Carbon\Carbon;

class PlanController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $owner = $user->owner_id
            ? User::find($user->owner_id)
            : $user;

        $limits = $owner->getPlanLimits();

        $daysRemaining = null;

        if ($owner->plan_end) {
            $daysRemaining = Carbon::parse(
                $owner->plan_end
            )->diffInDays(now(), false);
        }

        return view('plans.index', [
            'user' => $owner,
            'limits' => $limits,
            'shopsUsed' => Shop::where(
                'owner_id',
                $owner->id
            )->count(),

            'productsUsed' => Product::where(
                'owner_id',
                $owner->id
            )->count(),

            'usersUsed' => User::where(
                'owner_id',
                $owner->id
            )->count(),

            'daysRemaining' => $daysRemaining,
        ]);
    }
}