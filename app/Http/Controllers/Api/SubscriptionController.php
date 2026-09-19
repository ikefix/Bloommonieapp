<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    /**
     * Prices in Naira. Single source of truth: the app reads these via /subscription/plans.
     */
    private const PRICES = [
        'basic'    => ['monthly' => 5000,  'yearly' => 50000],
        'lite'     => ['monthly' => 7000,  'yearly' => 70000],
        'business' => ['monthly' => 10000, 'yearly' => 100000],
    ];

    /*
    |--------------------------------------------------------------------------
    | GET /api/subscription/plans   (public, no login needed)
    |--------------------------------------------------------------------------
    */
    public function plans()
    {
        // Public on purpose: prices aren't secret, and an expired user may
        // not have a valid login to show them with.
        $plans = collect(self::PRICES)->map(fn ($price, $id) => [
            'id'            => $id,
            'name'          => ucfirst($id),
            'monthly_price' => $price['monthly'],
            'yearly_price'  => $price['yearly'],
        ])->values();

        return response()->json([
            'status' => true,
            'data'   => ['plans' => $plans],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/subscription/initialize   { email, plan, billing }   (public, throttled)
    |--------------------------------------------------------------------------
    */
    public function initialize(Request $request)
    {
        $data = $request->validate([
            'email'   => 'required|email',
            'plan'    => 'required|in:basic,lite,business',
            'billing' => 'required|in:monthly,yearly',
        ]);

        // No login needed: an expired account often has no valid session.
        // The subscription lives on the admin's row (managers and cashiers
        // have no plan of their own), so only an admin's email is accepted.
        // Same message for "no such email" and "not an admin" so this can't
        // be used to find out which emails are registered.
        $user = User::where('email', trim($data['email']))
            ->where('role', 'admin')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'No account owner found with that email. Only the account owner (admin) can renew.',
            ], 404);
        }

        $plan      = $data['plan'];
        $billing   = $data['billing'];
        $amount    = self::PRICES[$plan][$billing] * 100; // kobo
        $reference = 'SUB_' . strtoupper(Str::random(16));

        // The app intercepts this URL inside the WebView, so it never has to exist.
        $callbackUrl = url('/payment/mobile-callback');

        $response = Http::withToken(config('services.paystack.secret'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email'        => $user->email,
                'amount'       => $amount,
                'reference'    => $reference,
                'callback_url' => $callbackUrl,
                'metadata'     => [
                    'plan'    => $plan,
                    'billing' => $billing,
                    'user_id' => $user->id,
                    'source'  => 'mobile_app',
                ],
            ]);

        if ($response->failed() || !$response->json('status')) {
            Log::error('Paystack initialize failed', ['body' => $response->body()]);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to initialize payment. Please try again.',
            ], 502);
        }

        // Remember what we charged for, so verify never has to trust client/metadata values.
        SubscriptionTransaction::create([
            'user_id'   => $user->id,
            'reference' => $reference,
            'plan'      => $plan,
            'billing'   => $billing,
            'amount'    => $amount,
            'status'    => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'data'   => [
                'authorization_url' => $response->json('data.authorization_url'),
                'reference'         => $reference,
                'callback_url'      => $callbackUrl,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/subscription/verify/{reference}   (public, throttled)
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request, string $reference)
    {
        // The reference is a random 16-character code created by initialize(),
        // and verifying only ever confirms a real Paystack payment, so no login
        // is needed here.
        $tx = SubscriptionTransaction::where('reference', $reference)->first();

        if (!$tx) {
            return response()->json([
                'status'  => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        [$ok, $message] = $this->verifyAndActivate($tx);

        $user = User::find($tx->user_id);

        return response()->json([
            'status'  => $ok,
            'message' => $message,
            'data'    => $ok ? [
                'plan'          => $user->plan,
                'plan_duration' => $user->plan_duration,
                'plan_end'      => $user->plan_end,
            ] : null,
        ], $ok ? 200 : 422);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/paystack/webhook   (public, no auth — signature protected)
    | Safety net: activates the plan even if the user closes the app after paying.
    |--------------------------------------------------------------------------
    */
    public function webhook(Request $request)
    {
        $expected = hash_hmac('sha512', $request->getContent(), config('services.paystack.secret'));

        if (!hash_equals($expected, (string) $request->header('x-paystack-signature'))) {
            return response()->json(['status' => false], 401);
        }

        if ($request->input('event') === 'charge.success') {
            $tx = SubscriptionTransaction::where('reference', $request->input('data.reference'))->first();

            if ($tx) {
                $this->verifyAndActivate($tx);
            }
        }

        return response()->json(['status' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Shared logic: verify with Paystack, then activate exactly once
    |--------------------------------------------------------------------------
    */
    private function verifyAndActivate(SubscriptionTransaction $tx): array
    {
        // Already processed (e.g. webhook got here first) — don't extend again.
        if ($tx->status === 'success') {
            return [true, 'Subscription activated successfully'];
        }

        $response = Http::withToken(config('services.paystack.secret'))
            ->get("https://api.paystack.co/transaction/verify/{$tx->reference}");

        if ($response->failed() || !$response->json('status')) {
            return [false, 'Payment verification failed'];
        }

        $data = $response->json('data');

        if (($data['status'] ?? null) !== 'success') {
            return [false, 'Payment was not completed'];
        }

        if ((int) $data['amount'] !== (int) $tx->amount) {
            Log::warning('Paystack amount mismatch', ['reference' => $tx->reference]);

            return [false, 'Payment amount does not match the selected plan'];
        }

        DB::transaction(function () use ($tx) {
            // Lock so webhook + verify can't both activate at the same time
            $tx = SubscriptionTransaction::lockForUpdate()->find($tx->id);

            if ($tx->status === 'success') {
                return;
            }

            $user = User::lockForUpdate()->find($tx->user_id);

            $isYearly = $tx->billing === 'yearly';

            $prefix = strtoupper(substr($tx->plan, 0, 3)); // BAS / LIT / BUS
            $type   = $isYearly ? 'YR' : 'MO';

            // product_key has a unique index. A collision here would roll the
            // transaction back AFTER the customer has been charged, so make
            // sure the key is free before using it.
            do {
                $productKey = $prefix . '-' . $type . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 10));
            } while (User::where('product_key', $productKey)->exists());

            // plan_start / plan_end / activated_at are DATE columns, so store dates.
            // addMonthNoOverflow: 31 Aug + 1 month = 30 Sep, not 1 Oct.
            $user->plan          = $tx->plan;
            $user->plan_duration = $isYearly ? '1_year' : '1_month';
            $user->plan_start    = now()->toDateString();
            $user->plan_end      = ($isYearly ? now()->addYear() : now()->addMonthNoOverflow())->toDateString();
            $user->is_activated  = true;
            $user->activated_at  = now()->toDateString();
            $user->product_key   = $productKey;
            $user->save();

            $tx->status  = 'success';
            $tx->paid_at = now();
            $tx->save();
        });

        return [true, 'Subscription activated successfully'];
    }
}