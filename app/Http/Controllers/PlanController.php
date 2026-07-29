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

        // Plan Status
        $status = 'Expired';

        if (
            $owner->plan_end &&
            Carbon::parse($owner->plan_end)->isFuture()
        ) {
            $status = 'Active';
        }

        // Days Remaining
        $daysRemaining = 0;

        if ($owner->plan_end) {

            $daysRemaining = Carbon::now()->diffInDays(
                Carbon::parse($owner->plan_end),
                false
            );
        }

        // Format Plan Duration
        $planDuration = match ($owner->plan_duration) {
            '1_month'  => '1 Month',
            '2_months' => '2 Months',
            '3_months' => '3 Months',
            '6_months' => '6 Months',
            '1_year'   => '1 Year',
            default    => ucfirst(
                str_replace('_', ' ', $owner->plan_duration)
            ),
        };

        return view('plans.index', [

            'user' => $owner,

            'limits' => $limits,

            'status' => $status,

            'planDuration' => $planDuration,

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
            )->count() + 1,

            'daysRemaining' => $daysRemaining,
        ]);
    }
}