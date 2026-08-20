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
     * GET /api/admin/reports/stock?shop_id=&search=&page=
     *
     * FIXED: the original had no owner scoping at all — Product::with('shop')
     * and Shop::orderBy('name')->get() pulled every product and every shop
     * from every business on the platform, not just the logged-in admin's.
     * Now scoped through shop_id, since that's the reliably owner-linked
     * relation (Shop has owner_id; whether Product itself has an owner_id
     * column wasn't confirmed, so this avoids assuming that column exists).
     */
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

        $ownerId = $user->owner_id ?? $user->id;
        $ownerShopIds = Shop::where('owner_id', $ownerId)->pluck('id');

        $shops = Shop::where('owner_id', $ownerId)->orderBy('name')->get();

        $tableQuery = Product::with('shop')
            ->whereIn('shop_id', $ownerShopIds);

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

        // Stats scoped to this owner's products only — previously counted
        // every product in the system for the low-stock/total cards.
        $statsQuery = Product::whereIn('shop_id', $ownerShopIds);
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
    // Same owner scoping fix applied here.
    public function downloadPdf(Request $request)
    {
        if (!$request->user()->hasFeature('stock_report')) {
            abort(403, 'This feature is not included in your current plan.');
        }

        $ownerId = $request->user()->owner_id ?? $request->user()->id;
        $ownerShopIds = Shop::where('owner_id', $ownerId)->pluck('id');

        $query = Product::with('shop')
            ->whereIn('shop_id', $ownerShopIds);

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