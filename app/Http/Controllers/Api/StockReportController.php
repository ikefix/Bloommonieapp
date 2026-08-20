<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class StockReportController extends Controller
{
    /**
     * GET /api/admin/reports/stock
     *
     * Query params:
     * shop_id=
     * search=
     *
     * FIXED: this whole method had ZERO owner scoping. Shop::orderBy(...)
     * and Product::with('shop') both pulled EVERY admin's shops/products
     * from the database, filtered only by shop_id/search when supplied —
     * that's why it listed products for every user, not just the logged-in
     * admin. Resolves the owner id the same way as the sales report
     * (owner_id ?? own id, so it works whether the owner or a staff/
     * sub-user account is logged in), then scopes every query to it.
     *
     * NOTE: this assumes `products` has its own `owner_id` column, the
     * same way `purchase_items` does. If it doesn't, tell me and I'll
     * scope through the shop relation instead.
     */
    public function index(Request $request)
    {
        $ownerId = auth()->user()->owner_id ?? auth()->id();

        // Get all shops for filter — owner-scoped only
        $shops = Shop::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();

        /**
         * =========================
         * TABLE QUERY
         * =========================
         */

        $tableQuery = Product::with('shop')
            ->where('owner_id', $ownerId);

        if ($request->filled('shop_id')) {
            $tableQuery->where(
                'shop_id',
                $request->shop_id
            );
        }

        if ($request->filled('search')) {
            $tableQuery->where(
                'name',
                'like',
                '%' . $request->search . '%'
            );
        }

        /**
         * =========================
         * PRODUCTS
         * =========================
         */

        $products = $tableQuery
            ->select(
                'products.id',
                'products.name',
                'products.shop_id',
                'products.stock_quantity',
                'products.stock_limit',
                'products.stock_unit',
                'products.unit_size',

                DB::raw(
                    'products.stock_quantity as opening_stock'
                ),

                DB::raw(
                    '0 as stock_added'
                ),

                DB::raw(
                    '0 as stock_sold'
                ),

                DB::raw(
                    'products.stock_quantity as remaining_stock'
                )
            )
            ->orderBy('products.name')
            ->paginate(20)
            ->withQueryString();

        /**
         * =========================
         * STATS
         * =========================
         *
         * FIXED: previously Product::query() with no filters at all — the
         * summary cards counted every admin's products regardless of the
         * shop/search filters applied to the table above, so the numbers
         * never matched what was actually listed.
         */

        $statsQuery = Product::query()->where('owner_id', $ownerId);

        if ($request->filled('shop_id')) {
            $statsQuery->where('shop_id', $request->shop_id);
        }

        if ($request->filled('search')) {
            $statsQuery->where('name', 'like', '%' . $request->search . '%');
        }

        $totalProducts = (clone $statsQuery)->count();

        $lowStockCount = (clone $statsQuery)
            ->whereColumn(
                'stock_quantity',
                '<=',
                'stock_limit'
            )
            ->count();

        /**
         * =========================
         * STOCK CHART
         * =========================
         */

        $stockChart = [
            'labels' => [
                'Low Stock',
                'Normal Stock'
            ],

            'data' => [
                $lowStockCount,
                max(
                    $totalProducts - $lowStockCount,
                    0
                )
            ],
        ];

        /**
         * =========================
         * API RESPONSE
         * =========================
         */

        return response()->json([
            'status' => true,

            'data' => [
                'shops' => $shops,

                'products' => $products,

                'total_products' => $totalProducts,

                'low_stock_count' => $lowStockCount,

                'stock_chart' => $stockChart,
            ],
        ]);
    }


    /**
     * GET /api/admin/reports/stock/pdf
     *
     * FIXED: same missing owner scoping as index() above.
     */
    public function downloadPdf(Request $request)
    {
        $ownerId = auth()->user()->owner_id ?? auth()->id();

        /**
         * =========================
         * BASE QUERY
         * =========================
         */

        $query = Product::with('shop')
            ->where('owner_id', $ownerId);

        if ($request->filled('shop_id')) {
            $query->where(
                'shop_id',
                $request->shop_id
            );
        }

        if ($request->filled('search')) {
            $query->where(
                'name',
                'like',
                '%' . $request->search . '%'
            );
        }

        /**
         * =========================
         * PRODUCTS
         * =========================
         *
         * Same select as index().
         * NO pagination for PDF.
         */

        $products = $query
            ->select(
                'products.id',
                'products.name',
                'products.shop_id',
                'products.stock_quantity',
                'products.stock_limit',
                'products.stock_unit',
                'products.unit_size',

                DB::raw(
                    'products.stock_quantity as opening_stock'
                ),

                DB::raw(
                    '0 as stock_added'
                ),

                DB::raw(
                    '0 as stock_sold'
                ),

                DB::raw(
                    'products.stock_quantity as remaining_stock'
                )
            )
            ->orderBy('products.name')
            ->get();

        /**
         * =========================
         * PDF
         * =========================
         */

        $pdf = Pdf::loadView(
            'admin.report.stock_pdf',
            compact('products')
        )->setPaper(
            'a4',
            'landscape'
        );

        return $pdf->download(
            'stock_inventory_report.pdf'
        );
    }
}