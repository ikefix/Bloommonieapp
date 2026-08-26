<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Notifications\StockReconciliationAlert;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OfflineSalesSyncController extends Controller
{
    /**
     * POST /api/admin/sales/sync
     *
     * Accepts a batch of sales made while the device was offline and
     * replays them server-side. Mirrors PurchaseItemController@store's
     * pricing/discount logic almost exactly, with three differences:
     *
     *  1. Never blocks for insufficient stock — an offline sale already
     *     happened in the real world (the customer already paid), so we
     *     can't retroactively refuse it. Stock is allowed to go negative.
     *
     *  2. Preserves the real time of sale (sold_at, set by the device)
     *     instead of using the sync time — otherwise every offline sale
     *     would show up on reports as happening the moment the phone
     *     reconnected to the internet, which would corrupt daily trends,
     *     best/worst day, etc.
     *
     *  3. Idempotent via client_sale_id — if the same batch gets synced
     *     twice (dropped connection mid-upload, app killed and retried,
     *     etc.), already-processed sales are skipped rather than
     *     duplicated.
     *
     * Request shape:
     * {
     *   "sales": [
     *     {
     *       "client_sale_id": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
     *       "sold_at": "2026-08-26 14:07:00",
     *       "customer_name": "optional",
     *       "customer_phone": "optional",
     *       "payment_method": "cash",
     *       "products": [
     *         { "product_id": 12, "quantity": 2, "discount_type": "none", "discount_value": 0 }
     *       ]
     *     }
     *   ]
     * }
     *
     * Response shape:
     * {
     *   "status": true,
     *   "results": [
     *     { "client_sale_id": "...", "status": "success", "transaction_id": "TXN-..." },
     *     { "client_sale_id": "...", "status": "duplicate" },
     *     { "client_sale_id": "...", "status": "error", "message": "..." }
     *   ]
     * }
     */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'sales'                            => 'required|array|min:1',
            'sales.*.client_sale_id'           => 'required|string|max:64',
            'sales.*.sold_at'                  => 'required|date',
            'sales.*.customer_name'            => 'nullable|string|max:255',
            'sales.*.customer_phone'           => 'nullable|string|max:20',
            'sales.*.payment_method'           => 'required|in:cash,card,transfer',
            'sales.*.products'                 => 'required|array|min:1',
            'sales.*.products.*.product_id'    => 'required|integer',
            'sales.*.products.*.quantity'      => 'required|integer|min:1',
            'sales.*.products.*.discount_type' => 'nullable|in:none,percentage,flat',
            'sales.*.products.*.discount_value'=> 'nullable|numeric|min:0',
        ]);

        $ownerId = auth()->user()->owner_id ?? auth()->id();
        $cashierId = auth()->id();

        $results = [];

        foreach ($validated['sales'] as $sale) {

            $clientSaleId = $sale['client_sale_id'];

            // ── IDEMPOTENCY CHECK ────────────────────────────────────────
            // If any row already exists with this client_sale_id, this
            // batch was already processed in a previous sync attempt.
            // Skip it entirely rather than re-inserting/re-decrementing.
            $alreadySynced = PurchaseItem::where('client_sale_id', $clientSaleId)->exists();

            if ($alreadySynced) {
                $results[] = [
                    'client_sale_id' => $clientSaleId,
                    'status'         => 'duplicate',
                ];
                continue;
            }

            try {
                DB::transaction(function () use ($sale, $clientSaleId, $ownerId, $cashierId, &$results) {

                    $soldAt = Carbon::parse($sale['sold_at']);

                    $transactionId = 'TXN-' . $soldAt->format('YmdHis') . '-' . rand(1000, 9999);

                    foreach ($sale['products'] as $item) {

                        // Scoped to this owner — prevents an offline sale
                        // payload from referencing another admin's product.
                        $product = Product::where('owner_id', $ownerId)
                            ->findOrFail($item['product_id']);

                        $quantityRequested = $item['quantity'];

                        // 🧮 Same discount math as the online store() flow.
                        $discountType = $item['discount_type'] ?? 'none';
                        $discountValue = $item['discount_value'] ?? 0;
                        $priceBeforeDiscount = $product->price * $quantityRequested;
                        $discountAmount = 0;

                        if ($discountType === 'percentage') {
                            $discountAmount = ($discountValue / 100) * $priceBeforeDiscount;
                        } elseif ($discountType === 'flat') {
                            $discountAmount = $discountValue;
                        }

                        $totalAfterDiscount = max($priceBeforeDiscount - $discountAmount, 0);

                        // forceFill + explicit created_at so the row reflects
                        // the real time of sale, not the sync time. Eloquent
                        // won't overwrite created_at on save() as long as
                        // it's already dirty before saving (checked via
                        // isDirty in updateTimestamps()), so this is safe.
                        $purchaseItem = new PurchaseItem();
                        $purchaseItem->forceFill([
                            'owner_id'         => $ownerId,
                            'customer_name'    => $sale['customer_name'] ?? null,
                            'customer_phone'   => $sale['customer_phone'] ?? null,
                            'product_id'       => $product->id,
                            'category_id'      => $product->category_id,
                            'quantity'         => $quantityRequested,
                            'total_price'      => $totalAfterDiscount,
                            'discount'         => $discountAmount,
                            'discount_type'    => $discountType,
                            'discount_value'   => $discountValue,
                            'payment_method'   => $sale['payment_method'],
                            'transaction_id'   => $transactionId,
                            'client_sale_id'   => $clientSaleId,
                            'synced_offline'   => true,
                            'shop_id'          => $product->shop_id,
                            'cashier_id'       => $cashierId,
                            'created_at'       => $soldAt,
                            'updated_at'       => now(),
                        ])->save();

                        // 🔄 Update stock — allowed to go negative. This is
                        // the deliberate trade-off: never block/reverse an
                        // offline sale that already happened for stock
                        // reasons, surface the discrepancy instead.
                        $product->decrement('stock_quantity', $quantityRequested);
                        $product->refresh();

                        if ($product->stock_quantity < 0) {
                            Notification::send(
                                auth()->user(),
                                new StockReconciliationAlert(
                                    $product,
                                    abs($product->stock_quantity),
                                    $clientSaleId
                                )
                            );
                        }
                        // Otherwise, keep existing low-stock alert behavior.
                        elseif ($product->stock_quantity <= $product->stock_limit) {
                            Notification::send(auth()->user(), new \App\Notifications\LowStockAlert($product));
                        }
                    }

                    $results[] = [
                        'client_sale_id' => $clientSaleId,
                        'status'         => 'success',
                        'transaction_id' => $transactionId,
                    ];
                });

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                $results[] = [
                    'client_sale_id' => $clientSaleId,
                    'status'         => 'error',
                    'message'        => 'One or more products in this sale no longer exist or do not belong to this account.',
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'client_sale_id' => $clientSaleId,
                    'status'         => 'error',
                    'message'        => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status'  => true,
            'results' => $results,
        ]);
    }
}