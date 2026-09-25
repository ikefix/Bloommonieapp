<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shop;
use App\Models\Product;
use Carbon\Carbon;

class PlanController extends Controller
{
    /**
     * Get current subscription plan information
     * for the authenticated user.
     */
    public function index()
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Get Plan Owner
        |--------------------------------------------------------------------------
        |
        | Cashiers/managers may belong to another owner.
        | In that case, use the owner's subscription.
        |
        */

        $owner = $user->owner_id
            ? User::find($user->owner_id)
            : $user;

        if (!$owner) {
            return response()->json([
                'success' => false,
                'message' => 'Plan owner not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Plan Limits
        |--------------------------------------------------------------------------
        */

        $limits = $owner->getPlanLimits();

        /*
        |--------------------------------------------------------------------------
        | Plan Status
        |--------------------------------------------------------------------------
        */

        $status = 'Expired';

        if (
            $owner->plan_end &&
            Carbon::parse($owner->plan_end)->isFuture()
        ) {
            $status = 'Active';
        }

        /*
        |--------------------------------------------------------------------------
        | Days Remaining
        |--------------------------------------------------------------------------
        */

        $daysRemaining = 0;

        if ($owner->plan_end) {
            $daysRemaining = max(
                0,
                Carbon::now()->diffInDays(
                    Carbon::parse($owner->plan_end),
                    false
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Plan Duration
        |--------------------------------------------------------------------------
        */

        $planDuration = match ($owner->plan_duration) {
            '1_month'  => '1 Month',
            '2_months' => '2 Months',
            '3_months' => '3 Months',
            '6_months' => '6 Months',
            '1_year'   => '1 Year',

            default => $owner->plan_duration
                ? ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $owner->plan_duration
                    )
                )
                : null,
        };

        /*
        |--------------------------------------------------------------------------
        | Usage
        |--------------------------------------------------------------------------
        */

        $shopsUsed = Shop::where(
            'owner_id',
            $owner->id
        )->count();

        $productsUsed = Product::where(
            'owner_id',
            $owner->id
        )->count();

        $usersUsed = User::where(
            'owner_id',
            $owner->id
        )->count() + 1;

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'data' => [
                'plan' => [
                    'name' => $owner->plan,
                    'duration' => $planDuration,
                    'duration_key' => $owner->plan_duration,

                    'status' => $status,

                    'start_date' => $owner->plan_start,
                    'end_date' => $owner->plan_end,

                    'days_remaining' => $daysRemaining,
                ],

                'limits' => $limits,

                'usage' => [
                    'shops' => $shopsUsed,
                    'products' => $productsUsed,
                    'users' => $usersUsed,
                ],

                'features' => $this->getFeatures($limits),
            ],
        ]);
    }

    /**
     * Convert plan limits into frontend feature access.
     */
    private function getFeatures($limits)
    {
        return [
            'stock_transfer' =>
                $limits['stock_transfer'] ?? false,

            'advanced_reports' =>
                $limits['advanced_reports'] ?? false,

            'expenses' =>
                $limits['expenses'] ?? false,

            'ai_assistant' =>
                $limits['ai_assistant'] ?? false,

            'whatsapp_sales' =>
                $limits['whatsapp_sales'] ?? false,

            'savings' =>
                $limits['savings'] ?? false,

            'multi_shop' =>
                $limits['shops'] ?? 1 > 1,

            'unlimited_products' =>
                ($limits['products'] ?? 0) === null,
        ];
    }
}