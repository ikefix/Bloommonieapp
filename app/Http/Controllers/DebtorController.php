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

        abort_unless(
            in_array($user->role, ['admin', 'manager', 'cashier']),
            403
        );

        $ownerId = $user->getOwnerId();

        // Managers only see their own shop
        $shops = Shop::query()
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

        return view(
            'debtors.index',
            compact('shops', 'cashiers')
        );
    }


    /**
     * JSON:
     * Returns all owing products.
     */
    public function data(Request $request)
    {
        return response()->json(
            $this->buildReport($request)
        );
    }


    /**
     * PDF:
     *
     * If product_id is supplied:
     *     Generate PDF for ONLY that product.
     *
     * If product_id is not supplied:
     *     Generate PDF for all owing products.
     */
    public function pdf(Request $request)
    {
        $user = auth()->user();
        $ownerId = $user->getOwnerId();

        /*
        |--------------------------------------------------------------------------
        | Build report using the selected product, cashier and shop
        |--------------------------------------------------------------------------
        */

        $report = $this->buildReport($request);


        /*
        |--------------------------------------------------------------------------
        | Shop name
        |--------------------------------------------------------------------------
        */

        $shop = $request->filled('shop_id')
            ? Shop::find($request->shop_id)
            : null;


        /*
        |--------------------------------------------------------------------------
        | Cashier name
        |--------------------------------------------------------------------------
        */

        $cashier = $request->filled('cashier_id')
            ? User::query()
                ->where('id', $request->cashier_id)
                ->where(function ($q) use ($ownerId) {
                    $q->where('owner_id', $ownerId)
                        ->orWhere('id', $ownerId);
                })
                ->first()
            : null;


        /*
        |--------------------------------------------------------------------------
        | Selected product name
        |--------------------------------------------------------------------------
        */

        $product = $request->filled('product_id')
            ? Product::find($request->product_id)
            : null;


        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView('debtors.pdf', [
            'products' => $report['products'],
            'summary' => $report['summary'],

            'shopName' => optional($shop)->name ?: 'All shops',

            'cashierName' => optional($cashier)->name ?: 'All cashiers',

            'productName' => optional($product)->name ?: 'All products',

            'generatedAt' => now()->format(
                'd M Y, h:i A'
            ),
        ])
        ->setPaper('a4', 'portrait');


        return $pdf->download(
            'Debtors-Log-' .
            ($product
                ? preg_replace(
                    '/[^A-Za-z0-9_-]/',
                    '-',
                    $product->name
                ) . '-'
                : ''
            ) .
            now()->format('Y-m-d') .
            '.pdf'
        );
    }


    /**
     * Build the debtors report.
     *
     * Filters:
     *
     * cashier_id
     * shop_id
     * product_id
     */
    private function buildReport(Request $request): array
    {
        $user = auth()->user();

        abort_unless(
            in_array($user->role, ['admin', 'manager', 'cashier']),
            403
        );

        $ownerId = $user->getOwnerId();


        /*
        |--------------------------------------------------------------------------
        | Everyone on this owner's team
        |--------------------------------------------------------------------------
        */

        $teamIds = User::query()
            ->where(function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId)
                    ->orWhere('id', $ownerId);
            })
            ->pluck('id');


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
                fn ($q) => $q->where(
                    'shop_id',
                    $user->shop_id
                )
            )

            ->when(
                $request->filled('shop_id'),
                fn ($q) => $q->where(
                    'shop_id',
                    $request->shop_id
                )
            )

            ->when(
                $request->filled('cashier_id'),
                fn ($q) => $q->where(
                    'user_id',
                    $request->cashier_id
                )
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
        | Product IDs
        |--------------------------------------------------------------------------
        */

        $productIds = $goodsByInvoice
            ->flatten(1)
            ->pluck('product_id')
            ->filter()
            ->unique();


        /*
        |--------------------------------------------------------------------------
        | If a product was selected, only use that product.
        |--------------------------------------------------------------------------
        */

        if ($request->filled('product_id')) {

            $selectedProductId =
                (int) $request->product_id;

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
            ->whereIn('id', $productIds)
            ->pluck('name', 'id');


        /*
        |--------------------------------------------------------------------------
        | Build lines
        |--------------------------------------------------------------------------
        */

        $lines = collect();


        foreach ($invoices as $invoice) {

            $goods =
                $goodsByInvoice[$invoice->id] ?? [];


            if (empty($goods)) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | If product selected, remove every other product
            |--------------------------------------------------------------------------
            */

            if ($request->filled('product_id')) {

                $selectedProductId =
                    (int) $request->product_id;

                $goods = array_values(
                    array_filter(
                        $goods,
                        function ($g) use ($selectedProductId) {

                            return !empty($g['product_id'])
                                && (int) $g['product_id']
                                === $selectedProductId;
                        }
                    )
                );


                if (empty($goods)) {
                    continue;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate total of the invoice lines
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | We use the ORIGINAL invoice goods total here.
            |
            */

            $allInvoiceGoods =
                $goodsByInvoice[$invoice->id] ?? [];

            $invoiceGoodsTotal = 0;

            foreach ($allInvoiceGoods as $g) {

                $invoiceGoodsTotal +=
                    (float) ($g['total_price'] ?? 0);
            }


            /*
            |--------------------------------------------------------------------------
            | Add selected product lines
            |--------------------------------------------------------------------------
            */

            foreach ($goods as $g) {

                if (empty($g['product_id'])) {
                    continue;
                }


                $lineTotal =
                    (float) ($g['total_price'] ?? 0);


                /*
                |--------------------------------------------------------------------------
                | Proportion of the invoice belonging to this product
                |--------------------------------------------------------------------------
                */

                $share =
                    $invoiceGoodsTotal > 0
                        ? $lineTotal / $invoiceGoodsTotal
                        : 1;


                $lines->push([

                    'product_id' =>
                        (int) $g['product_id'],

                    'product_name' =>
                        $productNames[
                            $g['product_id']
                        ] ?? 'Deleted product',

                    'customer_id' =>
                        (int) $invoice->customer_id,

                    'customer_name' =>
                        optional(
                            $invoice->customer
                        )->name,

                    'customer_phone' =>
                        optional(
                            $invoice->customer
                        )->phone,

                    'quantity' =>
                        (float) (
                            $g['quantity'] ?? 0
                        ),

                    'total' =>
                        $lineTotal,

                    'paid' =>
                        (float)
                        $invoice->amount_paid
                        * $share,

                    'balance' =>
                        (float)
                        $invoice->balance
                        * $share,
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Group by product
        |--------------------------------------------------------------------------
        */

        $products = $lines
            ->groupBy('product_id')
            ->map(
                function ($productLines) {

                    /*
                    |--------------------------------------------------------------------------
                    | Group customers
                    |--------------------------------------------------------------------------
                    */

                    $customers =
                        $productLines
                            ->groupBy('customer_id')
                            ->map(
                                function ($c) {

                                    return [

                                        'customer_id' =>
                                            $c->first()[
                                                'customer_id'
                                            ],

                                        'name' =>
                                            $c->first()[
                                                'customer_name'
                                            ],

                                        'phone' =>
                                            $c->first()[
                                                'customer_phone'
                                            ],

                                        'quantity' =>
                                            round(
                                                $c->sum(
                                                    'quantity'
                                                ),
                                                2
                                            ),

                                        'total' =>
                                            round(
                                                $c->sum(
                                                    'total'
                                                ),
                                                2
                                            ),

                                        'paid' =>
                                            round(
                                                $c->sum(
                                                    'paid'
                                                ),
                                                2
                                            ),

                                        'balance' =>
                                            round(
                                                $c->sum(
                                                    'balance'
                                                ),
                                                2
                                            ),
                                    ];
                                }
                            )
                            ->sortByDesc('balance')
                            ->values();


                    return [

                        'product_id' =>
                            $productLines
                                ->first()[
                                    'product_id'
                                ],

                        'name' =>
                            $productLines
                                ->first()[
                                    'product_name'
                                ],

                        'total_quantity' =>
                            round(
                                $customers
                                    ->sum('quantity'),
                                2
                            ),

                        'total_customers' =>
                            $customers->count(),

                        'total_amount' =>
                            round(
                                $customers
                                    ->sum('total'),
                                2
                            ),

                        'total_paid' =>
                            round(
                                $customers
                                    ->sum('paid'),
                                2
                            ),

                        'total_balance' =>
                            round(
                                $customers
                                    ->sum('balance'),
                                2
                            ),

                        'customers' =>
                            $customers,
                    ];
                }
            )
            ->sortByDesc('total_balance')
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Final report
        |--------------------------------------------------------------------------
        */

        return [

            'products' =>
                $products,

            'summary' => [

                'products' =>
                    $products->count(),

                'customers' =>
                    $lines
                        ->pluck('customer_id')
                        ->unique()
                        ->count(),

                'balance' =>
                    round(
                        $products
                            ->sum('total_balance'),
                        2
                    ),
            ],
        ];
    }
}
