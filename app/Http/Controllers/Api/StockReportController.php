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
    // GET /api/admin/reports/stock?shop_id=&search=&page=
    public function index(Request $request)
    {
        $user = $request->user();

        // Enforce the plan lock server-side — this is the real gate.
        // Any check added only in the Flutter app can be bypassed by calling
        // this endpoint directly (e.g. with curl/Postman + a valid token).
        if (!$user->hasFeature('stock_report')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => $user->getLimitMessage('feature', 'Stock / Inventory Report'),
                'data' => null,
            ]);
        }

        // FIXED: no owner scoping anywhere in this controller before — both
        // the shop dropdown and the product table pulled EVERY admin's
        // data, filtered only by shop_id/search if supplied.
        //
        // NOTE: this assumes `products` has its own `owner_id` column, the
        // same way `purchase_items` and `expenses` do elsewhere in the app.
        // If it doesn't, tell me and I'll scope through the shop relation
        // instead (whereHas('shop', fn($q) => $q->where('owner_id', $ownerId))).
        $ownerId = $user->owner_id ?? $user->id;

        $shops = Shop::where('owner_id', $ownerId)->orderBy('name')->get();

        $tableQuery = Product::with('shop')
            ->where('owner_id', $ownerId);

        if ($request->filled('shop_id')) {
            $tableQuery->where('shop_id', $request->shop_id);
        }

        if ($request->filled('search')) {
            $tableQuery->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $tableQuery
            ->select(
                'products.id',
                'products.name',
                'products.shop_id',
                'products.stock_quantity',
                'products.stock_limit',
                'products.stock_unit',
                'products.unit_size',
                DB::raw('products.stock_quantity as opening_stock'),
                DB::raw('0 as stock_added'),
                DB::raw('0 as stock_sold'),
                DB::raw('products.stock_quantity as remaining_stock')
            )
            ->orderBy('products.name')
            ->paginate(20)
            ->withQueryString();

        // FIXED: stats were computed off Product::query() with no owner
        // filter — totals/low-stock counts didn't match the (also broken)
        // table above. Scoped to the same owner + same shop/search filters
        // so the summary cards agree with the table.
        $statsQuery = Product::query()->where('owner_id', $ownerId);

        if ($request->filled('shop_id')) {
            $statsQuery->where('shop_id', $request->shop_id);
        }

        if ($request->filled('search')) {
            $statsQuery->where('name', 'like', '%' . $request->search . '%');
        }

        $totalProducts = (clone $statsQuery)->count();
        $lowStockCount = (clone $statsQuery)->whereColumn('stock_quantity', '<=', 'stock_limit')->count();

        $stockChart = [
            'labels' => ['Low Stock', 'Normal Stock'],
            'data'   => [$lowStockCount, max($totalProducts - $lowStockCount, 0)],
        ];

        return response()->json([
            'status' => true,
            'locked' => false,
            'data' => [
                'shops'           => $shops,
                'products'        => $products, // paginator serializes to {data, current_page, last_page, ...}
                'total_products'  => $totalProducts,
                'low_stock_count' => $lowStockCount,
                'stock_chart'     => $stockChart,
            ],
        ]);
    }

    // GET /api/admin/reports/stock/pdf?shop_id=&search=
    public function downloadPdf(Request $request)
    {
        $user = $request->user();

        if (!$user->hasFeature('stock_report')) {
            abort(403, 'This feature is not included in your current plan.');
        }

        // FIXED: same missing owner scoping as index() above.
        $ownerId = $user->owner_id ?? $user->id;

        $query = Product::with('shop')
            ->where('owner_id', $ownerId);

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query
            ->select(
                'products.id',
                'products.name',
                'products.shop_id',
                'products.stock_quantity',
                'products.stock_limit',
                'products.stock_unit',
                'products.unit_size',
                DB::raw('products.stock_quantity as opening_stock'),
                DB::raw('0 as stock_added'),
                DB::raw('0 as stock_sold'),
                DB::raw('products.stock_quantity as remaining_stock')
            )
            ->orderBy('products.name')
            ->get();

        $pdf = Pdf::loadView('admin.report.stock_pdf', compact('products'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('stock_inventory_report.pdf');
    }
}