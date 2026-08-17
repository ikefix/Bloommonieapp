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

        $shops = Shop::orderBy('name')->get();

        $tableQuery = Product::with('shop');

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

        $statsQuery = Product::query();
        $totalProducts = $statsQuery->count();
        $lowStockCount = $statsQuery->whereColumn('stock_quantity', '<=', 'stock_limit')->count();

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
    // Unchanged — still returns a binary PDF file.
    public function downloadPdf(Request $request)
    {
        if (!$request->user()->hasFeature('stock_report')) {
            abort(403, 'This feature is not included in your current plan.');
        }

        $query = Product::with('shop');

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