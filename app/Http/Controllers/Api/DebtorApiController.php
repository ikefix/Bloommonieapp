<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DebtorApiController extends Controller
{
    /**
     * Resolve authenticated user and validate role.
     */
    private function authorizedUser()
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, ['admin', 'manager', 'cashier'])) {
            abort(403, 'You are not authorized to access debtor reports.');
        }

        return $user;
    }

    /**
     * Return owner ID.
     */
    private function ownerId(): int
    {
        return (int) auth()->user()->getOwnerId();
    }

    /**
     * Get all team member IDs.
     */
    private function teamIds(): array
    {
        $ownerId = $this->ownerId();

        return User::query()
            ->where(function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId)
                    ->orWhere('id', $ownerId);
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Validate report filters.
     */
    private function validateFilters(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'nullable|integer|min:1',
            'cashier_id' => 'nullable|integer|min:1',
            'product_id' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid filter parameters.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return null;
    }

    /**
     * API index.
     *
     * Returns:
     * - Shops
     * - Cashiers
     * - Initial debtor report
     */
    public function index(Request $request)
    {
        $user = $this->authorizedUser();

        if ($error = $this->validateFilters($request)) {
            return $error;
        }

        $ownerId = $this->ownerId();

        $shops = Shop::query()
            ->where('owner_id', $ownerId)
            ->when(
                $user->role === 'manager',
                fn ($q) => $q->where('id', $user->shop_id)
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $cashiers = User::query()
            ->where('role', 'cashier')
            ->where('owner_id', $ownerId)
            ->when(
                $user->role === 'manager',
                fn ($q) => $q->where('shop_id', $user->shop_id)
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'message' => 'Debtor report retrieved successfully.',
            'data' => [
                'shops' => $shops,
                'cashiers' => $cashiers,
                'report' => $this->buildReport($request),
            ],
        ]);
    }

    /**
     * Return debtor report JSON.
     */
    public function data(Request $request)
    {
        $this->authorizedUser();

        if ($error = $this->validateFilters($request)) {
            return $error;
        }

        return response()->json([
            'success' => true,
            'message' => 'Debtor report retrieved successfully.',
            'data' => $this->buildReport($request),
        ]);
    }

    /**
     * Download debtor report PDF.
     *
     * product_id supplied:
     * PDF for selected product only.
     *
     * product_id not supplied:
     * PDF for all owing products.
     */
    public function pdf(Request $request)
    {
        $user = $this->authorizedUser();

        if ($error = $this->validateFilters($request)) {
            return $error;
        }

        $ownerId = $this->ownerId();

        $report = $this->buildReport($request);

        $shop = null;

        if ($request->filled('shop_id')) {
            $shop = Shop::query()
                ->where('owner_id', $ownerId)
                ->when(
                    $user->role === 'manager',
                    fn ($q) => $q->where('id', $user->shop_id)
                )
                ->find($request->shop_id);
        }

        $cashier = null;

        if ($request->filled('cashier_id')) {
            $cashier = User::query()
                ->where('role', 'cashier')
                ->where('owner_id', $ownerId)
                ->when(
                    $user->role === 'manager',
                    fn ($q) => $q->where('shop_id', $user->shop_id)
                )
                ->find($request->cashier_id);
        }

        $product = null;

        if ($request->filled('product_id')) {
            $product = Product::query()
                ->where('owner_id', $ownerId)
                ->find($request->product_id);
        }

        $pdf = Pdf::loadView('debtors.pdf', [
            'products' => $report['products'],
            'summary' => $report['summary'],

            'shopName' => $shop->name ?? 'All shops',

            'cashierName' => $cashier->name ?? 'All cashiers',

            'productName' => $product->name ?? 'All products',

            'generatedAt' => now()->format('d M Y, h:i A'),
        ])->setPaper('a4', 'portrait');

        $filename = 'Debtors-Log-' .
            ($product
                ? preg_replace('/[^A-Za-z0-9_-]/', '-', $product->name) . '-'
                : ''
            ) .
            now()->format('Y-m-d') .
            '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Build debtor report.
     */
    private function buildReport(Request $request): array
    {
        $user = $this->authorizedUser();

        $ownerId = $this->ownerId();

        $teamIds = $this->teamIds();

        /*
        |--------------------------------------------------------------------------
        | Validate selected shop belongs to owner
        |--------------------------------------------------------------------------
        */

        if ($request->filled('shop_id')) {
            $shopExists = Shop::query()
                ->where('owner_id', $ownerId)
                ->when(
                    $user->role === 'manager',
                    fn ($q) => $q->where('id', $user->shop_id)
                )
                ->where('id', $request->shop_id)
                ->exists();

            abort_unless($shopExists, 403, 'Invalid shop selection.');
        }

        /*
        |--------------------------------------------------------------------------
        | Validate selected cashier belongs to team
        |--------------------------------------------------------------------------
        */

        if ($request->filled('cashier_id')) {
            $cashierExists = User::query()
                ->where('id', $request->cashier_id)
                ->where('role', 'cashier')
                ->where('owner_id', $ownerId)
                ->when(
                    $user->role === 'manager',
                    fn ($q) => $q->where('shop_id', $user->shop_id)
                )
                ->exists();

            abort_unless($cashierExists, 403, 'Invalid cashier selection.');
        }

        /*
        |--------------------------------------------------------------------------
        | Get owing invoices
        |--------------------------------------------------------------------------
        */

        $invoices = Invoice::query()
            ->with('customer:id,name,phone')
            ->whereIn('user_id', $teamIds)
            ->where('payment_status', 'owing')
            ->where('balance', '>', 0)

            ->when(
                $user->role === 'manager',
                fn ($q) => $q->where('shop_id', $user->shop_id)
            )

            ->when(
                $request->filled('shop_id'),
                fn ($q) => $q->where('shop_id', $request->shop_id)
            )

            ->when(
                $request->filled('cashier_id'),
                fn ($q) => $q->where('user_id', $request->cashier_id)
            )

            ->get();

        /*
        |--------------------------------------------------------------------------
        | Normalize invoice goods
        |--------------------------------------------------------------------------
        */

        $goodsByInvoice = $invoices->mapWithKeys(
            function ($invoice) {

                $goods = $invoice->goods;

                if (!is_array($goods)) {
                    $goods = json_decode(
                        (string) $goods,
                        true
                    ) ?: [];
                }

                return [
                    $invoice->id => $goods
                ];
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Get product IDs
        |--------------------------------------------------------------------------
        */

        $productIds = $goodsByInvoice
            ->flatten(1)
            ->pluck('product_id')
            ->filter()
            ->unique();

        /*
        |--------------------------------------------------------------------------
        | Filter selected product
        |--------------------------------------------------------------------------
        */

        if ($request->filled('product_id')) {

            $selectedProductId = (int) $request->product_id;

            $productExists = Product::query()
                ->where('owner_id', $ownerId)
                ->where('id', $selectedProductId)
                ->exists();

            abort_unless(
                $productExists,
                403,
                'Invalid product selection.'
            );

            $productIds = $productIds
                ->filter(
                    fn ($id) =>
                        (int) $id === $selectedProductId
                )
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Product names
        |--------------------------------------------------------------------------
        */

        $productNames = Product::query()
            ->where('owner_id', $ownerId)
            ->whereIn('id', $productIds)
            ->pluck('name', 'id');

        /*
        |--------------------------------------------------------------------------
        | Build debtor lines
        |--------------------------------------------------------------------------
        */

        $lines = collect();

        foreach ($invoices as $invoice) {

            $goods = $goodsByInvoice[$invoice->id] ?? [];

            if (empty($goods)) {
                continue;
            }

            $allInvoiceGoods = $goods;

            /*
            |--------------------------------------------------------------------------
            | Filter selected product
            |--------------------------------------------------------------------------
            */

            if ($request->filled('product_id')) {

                $selectedProductId = (int) $request->product_id;

                $goods = array_values(
                    array_filter(
                        $goods,
                        fn ($g) =>
                            !empty($g['product_id']) &&
                            (int) $g['product_id'] === $selectedProductId
                    )
                );

                if (empty($goods)) {
                    continue;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate original invoice goods total
            |--------------------------------------------------------------------------
            */

            $invoiceGoodsTotal = 0;

            foreach ($allInvoiceGoods as $g) {
                $invoiceGoodsTotal +=
                    (float) ($g['total_price'] ?? 0);
            }

            /*
            |--------------------------------------------------------------------------
            | Add product lines
            |--------------------------------------------------------------------------
            */

            foreach ($goods as $g) {

                if (empty($g['product_id'])) {
                    continue;
                }

                $productId = (int) $g['product_id'];

                // Do not include products belonging to another owner.
                if (!isset($productNames[$productId])) {
                    continue;
                }

                $lineTotal = (float) ($g['total_price'] ?? 0);

                $share = $invoiceGoodsTotal > 0
                    ? $lineTotal / $invoiceGoodsTotal
                    : 0;

                $lines->push([

                    'product_id' => $productId,

                    'product_name' => $productNames[$productId],

                    'customer_id' => (int) $invoice->customer_id,

                    'customer_name' => optional($invoice->customer)->name,

                    'customer_phone' => optional($invoice->customer)->phone,

                    'quantity' => (float) ($g['quantity'] ?? 0),

                    'total' => $lineTotal,

                    'paid' => (float) $invoice->amount_paid * $share,

                    'balance' => (float) $invoice->balance * $share,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Group by product and customer
        |--------------------------------------------------------------------------
        */

        $products = $lines
            ->groupBy('product_id')
            ->map(function ($productLines) {

                $customers = $productLines
                    ->groupBy('customer_id')
                    ->map(function ($customerLines) {

                        return [

                            'customer_id' => $customerLines->first()['customer_id'],

                            'name' => $customerLines->first()['customer_name'],

                            'phone' => $customerLines->first()['customer_phone'],

                            'quantity' => round($customerLines->sum('quantity'), 2),

                            'total' => round($customerLines->sum('total'), 2),

                            'paid' => round($customerLines->sum('paid'), 2),

                            'balance' => round($customerLines->sum('balance'), 2),
                        ];
                    })
                    ->sortByDesc('balance')
                    ->values();

                return [

                    'product_id' => $productLines->first()['product_id'],

                    'name' => $productLines->first()['product_name'],

                    'total_quantity' => round($customers->sum('quantity'), 2),

                    'total_customers' => $customers->count(),

                    'total_amount' => round($customers->sum('total'), 2),

                    'total_paid' => round($customers->sum('paid'), 2),

                    'total_balance' => round($customers->sum('balance'), 2),

                    'customers' => $customers,
                ];
            })
            ->sortByDesc('total_balance')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Return final report
        |--------------------------------------------------------------------------
        */

        return [

            'products' => $products,

            'summary' => [

                'products' => $products->count(),

                'customers' => $lines
                    ->pluck('customer_id')
                    ->unique()
                    ->count(),

                'balance' => round(
                    $products->sum('total_balance'),
                    2
                ),
            ],
        ];
    }
}
