<?php

namespace App\Http\Controllers;

use App\Models\PurchaseItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;

class DebtorController extends Controller
{
    /**
     * Debtors log page.
     */
    public function index()
    {
        $user = auth()->user();

        abort_unless(in_array($user->role, ['admin', 'manager', 'cashier']), 403);

        $ownerId = $user->getOwnerId();

        // Managers only see their own shop
        $shops = Shop::query()
            ->when($user->role === 'manager', fn ($q) => $q->where('id', $user->shop_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $cashiers = User::query()
            ->where('role', 'cashier')
            ->where('owner_id', $ownerId)
            ->when($user->role === 'manager', fn ($q) => $q->where('shop_id', $user->shop_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('debtors.index', compact('shops', 'cashiers'));
    }

    /**
     * JSON: owing products, each with the customers who owe for it.
     *
     * Debt is stored per invoice (amount_paid / balance), not per product, so for an
     * invoice with several products the paid and owing amounts are split across its
     * product lines in proportion to each line's share of the invoice.
     */
    public function data(Request $request)
    {
        $user = auth()->user();

        abort_unless(in_array($user->role, ['admin', 'manager', 'cashier']), 403);

        $ownerId = $user->getOwnerId();

        $rows = PurchaseItem::query()
            ->join('invoices', 'invoices.id', '=', 'purchase_items.invoice_id')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->join('products', 'products.id', '=', 'purchase_items.product_id')
            ->where('purchase_items.owner_id', $ownerId)
            ->where('invoices.payment_status', 'owing')
            ->where('invoices.balance', '>', 0)
            ->when($user->role === 'manager', fn ($q) => $q->where('invoices.shop_id', $user->shop_id))
            ->when($request->filled('shop_id'), fn ($q) => $q->where('invoices.shop_id', $request->shop_id))
            ->when($request->filled('cashier_id'), fn ($q) => $q->where('invoices.user_id', $request->cashier_id))
            ->get([
                'purchase_items.product_id',
                'products.name as product_name',
                'purchase_items.quantity',
                'purchase_items.total_price as line_total',
                'invoices.id as invoice_id',
                'invoices.amount_paid as invoice_paid',
                'invoices.balance as invoice_balance',
                'customers.id as customer_id',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
            ]);

        // Per-invoice totals used to split paid / balance across product lines
        $invoiceLineSums   = $rows->groupBy('invoice_id')->map(fn ($g) => (float) $g->sum('line_total'));
        $invoiceLineCounts = $rows->groupBy('invoice_id')->map->count();

        $lines = $rows->map(function ($r) use ($invoiceLineSums, $invoiceLineCounts) {
            $sum   = $invoiceLineSums[$r->invoice_id] ?? 0;
            $share = $sum > 0
                ? ((float) $r->line_total / $sum)
                : (1 / max($invoiceLineCounts[$r->invoice_id] ?? 1, 1));

            return [
                'product_id'     => (int) $r->product_id,
                'product_name'   => $r->product_name,
                'customer_id'    => (int) $r->customer_id,
                'customer_name'  => $r->customer_name,
                'customer_phone' => $r->customer_phone,
                'quantity'       => (float) $r->quantity,
                'total'          => (float) $r->line_total,
                'paid'           => (float) $r->invoice_paid * $share,
                'balance'        => (float) $r->invoice_balance * $share,
            ];
        });

        $products = $lines->groupBy('product_id')->map(function ($productLines) {
            $customers = $productLines->groupBy('customer_id')->map(function ($c) {
                return [
                    'customer_id' => $c->first()['customer_id'],
                    'name'        => $c->first()['customer_name'],
                    'phone'       => $c->first()['customer_phone'],
                    'quantity'    => round($c->sum('quantity'), 2),
                    'total'       => round($c->sum('total'), 2),
                    'paid'        => round($c->sum('paid'), 2),
                    'balance'     => round($c->sum('balance'), 2),
                ];
            })->sortByDesc('balance')->values();

            return [
                'product_id'      => $productLines->first()['product_id'],
                'name'            => $productLines->first()['product_name'],
                'total_quantity'  => round($customers->sum('quantity'), 2),
                'total_customers' => $customers->count(),
                'total_amount'    => round($customers->sum('total'), 2),
                'total_paid'      => round($customers->sum('paid'), 2),
                'total_balance'   => round($customers->sum('balance'), 2),
                'customers'       => $customers,
            ];
        })->sortByDesc('total_balance')->values();

        return response()->json([
            'products' => $products,
            'summary'  => [
                'products'  => $products->count(),
                'customers' => $lines->pluck('customer_id')->unique()->count(),
                'balance'   => round($products->sum('total_balance'), 2),
            ],
        ]);
    }
}