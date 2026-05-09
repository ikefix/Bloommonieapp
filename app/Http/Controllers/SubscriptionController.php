<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class SubscriptionController extends Controller
{
   public function pay($plan, $billing)
{
    $user = auth()->user();

    $monthlyPrices = [
        'basic' => 5000,
        'lite' => 7000,
        'business' => 10000,
    ];

    $yearlyPrices = [
        'basic' => 50000,
        'lite' => 70000,
        'business' => 100000,
    ];

    if ($billing == 'yearly') {
        $prices = $yearlyPrices;
    } else {
        $prices = $monthlyPrices;
        $billing = 'monthly';
    }

    if (!isset($prices[$plan])) {
        return back()->with('error', 'Invalid plan');
    }

    $amount = $prices[$plan] * 100;

    $reference = 'SUB_' . uniqid();

    $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
        ->post('https://api.paystack.co/transaction/initialize', [

            'email' => $user->email,
            'amount' => $amount,
            'reference' => $reference,
            'callback_url' => route('payment.callback'),

            'metadata' => [
                'plan' => $plan,
                'billing' => $billing, // 🔥 FIXED
                'user_id' => $user->id,
            ],
        ]);

    $result = $response->json();

    if (!$result['status']) {
        return back()->with('error', 'Unable to initialize payment');
    }

    return redirect($result['data']['authorization_url']);
}

    public function callback(Request $request)
{
    $reference = $request->reference;

    $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
        ->get("https://api.paystack.co/transaction/verify/{$reference}");

    $result = $response->json();

    if (!$result['status']) {
        return redirect('/subscription-expired')
            ->with('error', 'Payment verification failed');
    }

    if ($result['data']['status'] !== 'success') {
        return redirect('/subscription-expired')
            ->with('error', 'Payment failed');
    }

    $meta = $result['data']['metadata'];

    $user = User::find($meta['user_id']);

    if (!$user) {
        return redirect('/login');
    }

    $plan = $meta['plan'];           // basic/lite/business
    $billing = $meta['billing'];    // monthly/yearly

    /*
    |--------------------------------------------------------------------------
    | DETERMINE DURATION
    |--------------------------------------------------------------------------
    */

    if ($billing == 'yearly') {
        $duration = '1_year';
        $endDate = now()->addYear();
    } else {
        $duration = '1_month';
        $endDate = now()->addMonth();
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE PRODUCT KEY
    |--------------------------------------------------------------------------
    */

    $prefix = strtoupper(substr($plan, 0, 3)); // BAS / LIT / BUS
    $type   = $billing == 'yearly' ? 'YR' : 'MO';

    $productKey = $prefix . '-' . $type . '-' .
        strtoupper(substr(md5(uniqid()), 0, 10));

    /*
    |--------------------------------------------------------------------------
    | SAVE USER TABLE
    |--------------------------------------------------------------------------
    */

    $user->plan = $plan;
    $user->plan_duration = $duration;
    $user->plan_start = now();
    $user->plan_end = $endDate;

    $user->is_activated = true;
    $user->activated_at = now();

    $user->product_key = $productKey;

    $user->save();

    return redirect('/admin-dashboard')
        ->with('success', 'Subscription activated successfully');
}
}