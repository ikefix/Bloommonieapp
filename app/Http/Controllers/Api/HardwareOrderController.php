<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Hardwareproduct;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HardwareOrderController extends Controller
{
    /**
     * Initialize hardware order and Paystack payment.
     */
    public function initializePayment(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',

            'alternate_phone' => 'nullable|string|max:30',
            'company_name' => 'nullable|string|max:255',

            'address' => 'required|string|max:1000',
            'apartment' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:30',
            'country' => 'required|string|max:100',

            'delivery_instructions' => 'nullable|string|max:2000',
            'order_note' => 'nullable|string|max:2000',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1|max:100',
        ]);

        try {

            // Create order and order items
            $order = DB::transaction(function () use ($validated) {

                $quantities = [];

                foreach ($validated['items'] as $item) {
                    $id = $item['product_id'];

                    $quantities[$id] = ($quantities[$id] ?? 0)
                        + $item['quantity'];
                }

                $products = Hardwareproduct::whereIn(
                    'id',
                    array_keys($quantities)
                )
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

                if ($products->count() !== count($quantities)) {
                    throw new \Exception(
                        'One or more products are no longer available.'
                    );
                }

                $subtotal = 0;

                foreach ($quantities as $productId => $quantity) {

                    $product = $products->get($productId);

                    if ($quantity > (int) $product->stock) {
                        throw new \Exception(
                            $product->name .
                            ' does not have enough stock available.'
                        );
                    }

                    $subtotal +=
                        (float) $product->price * $quantity;
                }

                // Delivery is zero until delivery pricing is configured.
                $deliveryFee = 0;
                $discount = 0;

                $total = $subtotal + $deliveryFee - $discount;

                $order = Order::create([
                    'user_id' => Auth::id(),

                    'order_number' => 'BMH-' .
                        strtoupper(Str::random(10)),

                    'full_name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],

                    'alternate_phone' =>
                        $validated['alternate_phone'] ?? null,

                    'company_name' =>
                        $validated['company_name'] ?? null,

                    'address' => $validated['address'],
                    'apartment' => $validated['apartment'] ?? null,
                    'city' => $validated['city'],
                    'state' => $validated['state'],
                    'postal_code' => $validated['postal_code'] ?? null,
                    'country' => $validated['country'],

                    'delivery_instructions' =>
                        $validated['delivery_instructions'] ?? null,

                    'order_note' =>
                        $validated['order_note'] ?? null,

                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'discount' => $discount,
                    'total' => $total,

                    'currency' => 'NGN',
                    'payment_method' => 'paystack',
                    'payment_status' => 'pending',
                    'order_status' => 'pending',
                ]);

                foreach ($quantities as $productId => $quantity) {

                    $product = $products->get($productId);

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_description' => $product->description,
                        'product_image' => $product->image,

                        'unit_price' => $product->price,
                        'quantity' => $quantity,

                        'total_price' =>
                            (float) $product->price * $quantity,
                    ]);
                }

                return $order;
            });

            // Generate payment reference
            $reference = 'BMH-' .
                strtoupper(Str::random(16));

            $order->update([
                'payment_reference' => $reference,
            ]);

            // Initialize Paystack
            $response = Http::timeout(30)
                ->withToken(config('services.paystack.secret_key'))
                ->acceptJson()
                ->post(
                    rtrim(
                        config('services.paystack.payment_url'),
                        '/'
                    ) . '/transaction/initialize',

                    [
                        'email' => $order->email,

                        // Paystack accepts amounts in kobo.
                        'amount' => (int) round(
                            (float) $order->total * 100
                        ),

                        'currency' => 'NGN',
                        'reference' => $reference,

                        'callback_url' =>
                            rtrim(
                                config('services.frontend.url'),
                                '/'
                            ) . '/order-success',
                    ]
                );

            if (!$response->successful() ||
                !$response->json('status')) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Unable to initialize payment. Please try again.',
                    'order_number' => $order->order_number,
                ], 502);
            }

            $paymentData = $response->json('data');

            $order->update([
                'payment_url' => $paymentData['authorization_url'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created. Continue to payment.',

                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->total,
                    'payment_status' => $order->payment_status,
                ],

                'payment' => [
                    'reference' => $reference,
                    'authorization_url' =>
                        $paymentData['authorization_url'],
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }


    /**
     * Verify Paystack transaction.
     */
    public function verifyPayment($reference)
    {
        $order = Order::where(
            'payment_reference',
            $reference
        )->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Prevent duplicate payment processing.
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Payment already verified.',
                'order' => $order->load('items'),
            ]);
        }

        try {

            $response = Http::timeout(30)
                ->withToken(config('services.paystack.secret_key'))
                ->acceptJson()
                ->get(
                    rtrim(
                        config('services.paystack.payment_url'),
                        '/'
                    ) . '/transaction/verify/' .
                    urlencode($reference)
                );

            if (!$response->successful() ||
                !$response->json('status')) {

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to verify payment.',
                ], 502);
            }

            $payment = $response->json('data');

            $expectedAmount = (int) round(
                (float) $order->total * 100
            );

            if (
                ($payment['status'] ?? '') !== 'success' ||
                ($payment['reference'] ?? '') !== $reference ||
                (int) ($payment['amount'] ?? 0) !== $expectedAmount ||
                ($payment['currency'] ?? '') !== 'NGN'
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Payment has not been successfully verified.',
                ], 422);
            }

            DB::transaction(function () use ($reference) {

                $lockedOrder = Order::where(
                    'payment_reference',
                    $reference
                )
                ->lockForUpdate()
                ->firstOrFail();

                if ($lockedOrder->payment_status === 'paid') {
                    return;
                }

                $items = $lockedOrder->items;

                // Verify stock again before deducting it.
                $products = Hardwareproduct::whereIn(
                    'id',
                    $items->pluck('product_id')->filter()->unique()
                )
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

                $stockIssue = false;

                foreach ($items as $item) {

                    $product = $products->get($item->product_id);

                    if (
                        !$product ||
                        (int) $product->stock < $item->quantity
                    ) {
                        $stockIssue = true;
                        break;
                    }
                }

                // Payment is genuine, so record it even if stock
                // needs manual review.
                $lockedOrder->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'order_status' => $stockIssue
                        ? 'stock_review'
                        : 'processing',
                ]);

                if (!$stockIssue) {

                    foreach ($items as $item) {

                        $product = $products->get($item->product_id);

                        $product->decrement(
                            'stock',
                            $item->quantity
                        );
                    }
                }
            });

            $verifiedOrder = Order::with('items')
                ->where('payment_reference', $reference)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Payment successfully verified.',
                'order' => $verifiedOrder,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed.',
            ], 500);
        }
    }
}
