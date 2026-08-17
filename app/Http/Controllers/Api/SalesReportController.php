<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Shop;

class SalesReportController extends Controller
{
    // GET /api/admin/reports/sales?start_date=&end_date=&shop_id=
    // FIXED: was filtering by auth()->id() directly, which only matches
    // rows where the logged-in user's own ID equals owner_id — same pattern
    // bug flagged earlier. Now uses the owner_id ?? id fallback used
    // elsewhere in the app (see CustomerController::searchSuggestions).
    public function index(Request $request)
    {
        $ownerId = auth()->user()->owner_id ?? auth()->id();

        $shops = Shop::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();

        $baseQuery = PurchaseItem::query()
            ->where('purchase_items.owner_id', $ownerId);

        $chartQuery = PurchaseItem::query()
            ->where('purchase_items.owner_id', $ownerId);

        /**
         * =========================
         * DATE FILTER
         * =========================
         */

        if ($request->filled('start_date') && $request->filled('end_date')) {

            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();

            $baseQuery->whereBetween('purchase_items.created_at', [$start, $end]);
            $chartQuery->whereBetween('purchase_items.created_at', [$start, $end]);

            $chartStart = $start->copy();
            $chartEnd = $end->copy();

        } else {

            $baseQuery->whereDate('purchase_items.created_at', Carbon::today());

            $chartStart = Carbon::today()->subDays(6)->startOfDay();
            $chartEnd = Carbon::today()->endOfDay();

            $chartQuery->whereBetween('purchase_items.created_at', [$chartStart, $chartEnd]);
        }

        /**
         * =========================
         * SHOP FILTER
         * =========================
         */

        if ($request->filled('shop_id')) {
            $baseQuery->where('purchase_items.shop_id', $request->shop_id);
            $chartQuery->where('purchase_items.shop_id', $request->shop_id);
        }

        /**
         * =========================
         * CHART DATA
         * =========================
         */

        $dbSales = $chartQuery
            ->select(
                DB::raw('DATE(purchase_items.created_at) as date'),
                DB::raw('SUM(purchase_items.total_price) as total')
            )
            ->groupBy('date')
            ->pluck('total', 'date');

        $salesByDay = collect();

        $daysDiff = $chartStart->diffInDays($chartEnd);

        for ($i = 0; $i <= $daysDiff; $i++) {

            $date = $chartStart->copy()->addDays($i)->toDateString();

            $salesByDay->push([
                'date'  => $date,
                'total' => $dbSales[$date] ?? 0,
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'shops' => $shops,

                'total_sales' => (clone $baseQuery)
                    ->sum('purchase_items.total_price'),

                'total_transactions' => (clone $baseQuery)
                    ->distinct()
                    ->count('transaction_id'),

                'top_products' => (clone $baseQuery)
                    ->join('products', 'purchase_items.product_id', '=', 'products.id')
                    ->select(
                        'products.name as product_name',
                        DB::raw('SUM(purchase_items.quantity) as total_sold')
                    )
                    ->groupBy('products.id', 'products.name')
                    ->orderByDesc('total_sold')
                    ->limit(5)
                    ->get(),

                'sales_by_day' => $salesByDay,
            ],
        ]);
    }

    // GET /api/admin/reports/sales/pdf?start_date=&end_date=&shop_id=
    // Unchanged behavior except the same owner_id fallback fix as index().
    public function downloadPdf(Request $request)
    {
        $ownerId = auth()->user()->owner_id ?? auth()->id();

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $shopId = $request->shop_id;

        $query = DB::table('purchase_items')
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->join('shops', 'purchase_items.shop_id', '=', 'shops.id')
            ->where('purchase_items.owner_id', $ownerId);

        if ($startDate && $endDate) {
            $query->whereBetween('purchase_items.created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);
        }

        if ($shopId) {
            $query->where('purchase_items.shop_id', $shopId);
        }

        $topProducts = (clone $query)
            ->select(
                'products.name as product_name',
                DB::raw('SUM(purchase_items.quantity) as total_sold')
            )
            ->groupBy('products.name')
            ->orderByDesc('total_sold')
            ->get();

        $totalSales = (clone $query)->sum('purchase_items.total_price');

        $totalTransactions = (clone $query)
            ->select('purchase_items.transaction_id')
            ->distinct()
            ->count();

        $shop = $shopId
            ? Shop::where('owner_id', $ownerId)->find($shopId)
            : null;

        $pdf = Pdf::loadView(
            'admin.report.sales_report_pdf',
            compact('topProducts', 'totalSales', 'totalTransactions', 'startDate', 'endDate', 'shop')
        )->setPaper('a4', 'portrait');

        return $pdf->download('sales_report.pdf');
    }
}