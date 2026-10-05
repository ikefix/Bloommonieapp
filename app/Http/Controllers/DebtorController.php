<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
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
     */
    public function data(Request $request)
    {
        return response()->json($this->buildReport($request));
    }

    /**
     * PDF: every owing product with its customer table (honours the cashier / shop filters).
     */
    public function pdf(Request $request)
    {
        $user = auth()->user();
        $ownerId = $user->getOwnerId();

        $report = $this->buildReport($request);

        $shop = $request->filled('shop_id') ? Shop::find($request->shop_id) : null;

        // Only resolve a cashier name if they belong to this owner's team
        $cashier = $request->filled('cashier_id')
            ? User::query()
                ->where('id', $request->cashier_id)
                ->where(function ($q) use ($ownerId) {
                    $q->where('owner_id', $ownerId)->orWhere('id', $ownerId);
                })
                ->first()
            : null;

        $pdf = Pdf::loadView('debtors.pdf', [
            'products'    => $report['products'],
            'summary'     => $report['summary'],
            'shopName'    => optional($shop)->name ?: 'All shops',
            'cashierName' => optional($cashier)->name ?: 'All cashiers',
            'generatedAt' => now()->format('d M Y, h:i A'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Debtors-Log-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Builds the debtors report: owing products, each with the customers who owe for it.
     *
     * Products come from each owing invoice's `goods` list. Debt is stored per invoice
     * (amount_paid / balance), not per product, so for an invoice with several products
     * the paid and owing amounts are split across its lines in proportion to each
     * line's share of the invoice.
     */
    private function buildReport(Request $request): array
    {
        $user = auth()->user();

        abort_unless(in_array($user->role, ['admin', 'manager', 'cashier']), 403);

        $ownerId = $user->getOwnerId();

        // Everyone on this owner's team (owner + their staff), so we only see our own invoices
        $teamIds = User::query()
            ->where(function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId)->orWhere('id', $ownerId);
            })
            ->pluck('id');

        $invoices = Invoice::query()
            ->with('customer:id,name,phone')
            ->whereIn('user_id', $teamIds)
            ->where('payment_status', 'owing')
            ->where('balance', '>', 0)
            ->when($user->role === 'manager', fn ($q) => $q->where('shop_id', $user->shop_id))
            ->when($request->filled('shop_id'), fn ($q) => $q->where('shop_id', $request->shop_id))
            ->when($request->filled('cashier_id'), fn ($q) => $q->where('user_id', $request->cashier_id))
            ->get();

        // Normalise each invoice's goods into an array
        $goodsByInvoice = $invoices->mapWithKeys(function ($invoice) {
            $goods = $invoice->goods;

            if (!is_array($goods)) {
                $goods = json_decode((string) $goods, true) ?: [];
            }

            return [$invoice->id => $goods];
        });

        $productNames = Product::query()
            ->whereIn('id', $goodsByInvoice->flatten(1)->pluck('product_id')->filter()->unique())
            ->pluck('name', 'id');

        $lines = collect();

        foreach ($invoices as $invoice) {
            $goods = $goodsByInvoice[$invoice->id] ?? [];

            if (empty($goods)) {
                continue;
            }

            $sum = 0;
            foreach ($goods as $g) {
                $sum += (float) ($g['total_price'] ?? 0);
            }

            foreach ($goods as $g) {
                if (empty($g['product_id'])) {
                    continue;
                }

                $lineTotal = (float) ($g['total_price'] ?? 0);
                $share     = $sum > 0 ? $lineTotal / $sum : 1 / count($goods);

                $lines->push([
                    'product_id'     => (int) $g['product_id'],
                    'product_name'   => $productNames[$g['product_id']] ?? 'Deleted product',
                    'customer_id'    => (int) $invoice->customer_id,
                    'customer_name'  => optional($invoice->customer)->name,
                    'customer_phone' => optional($invoice->customer)->phone,
                    'quantity'       => (float) ($g['quantity'] ?? 0),
                    'total'          => $lineTotal,
                    'paid'           => (float) $invoice->amount_paid * $share,
                    'balance'        => (float) $invoice->balance * $share,
                ]);
            }
        }

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

        return [
            'products' => $products,
            'summary'  => [
                'products'  => $products->count(),
                'customers' => $lines->pluck('customer_id')->unique()->count(),
                'balance'   => round($products->sum('total_balance'), 2),
            ],
        ];
    }
}