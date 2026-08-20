<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Shop;

class SalesReportController extends Controller
{
    /**
     * GET /api/admin/reports/sales
     *
     * Query params:
     * start_date=YYYY-MM-DD
     * end_date=YYYY-MM-DD
     * shop_id=ID
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Use the exact same owner logic used when creating sales.
        $ownerId = $user->getOwnerId();

        /*
        |--------------------------------------------------------------------------
        | SHOPS
        |--------------------------------------------------------------------------
        */

        $shops = Shop::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | DATE RANGE
        |--------------------------------------------------------------------------
        */

        $startDate = null;
        $endDate = null;

        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY DATE RANGE
        |--------------------------------------------------------------------------
        |
        | No dates:
        |   Today
        |
        | Start only:
        |   Start date -> today
        |
        | End only:
        |   Beginning -> end date
        |
        | Both:
        |   Selected range
        |
        */

        if (!$startDate && !$endDate) {

            $summaryStart = Carbon::today()->startOfDay();
            $summaryEnd   = Carbon::today()->endOfDay();

        } elseif ($startDate && !$endDate) {

            $summaryStart = $startDate;
            $summaryEnd   = Carbon::today()->endOfDay();

        } elseif (!$startDate && $endDate) {

            $summaryStart = Carbon::create(2020, 1, 1)->startOfDay();
            $summaryEnd   = $endDate;

        } else {

            $summaryStart = $startDate;
            $summaryEnd   = $endDate;
        }

        /*
        |--------------------------------------------------------------------------
        | BASE QUERY
        |--------------------------------------------------------------------------
        |
        | Using DB::table intentionally here.
        |
        | PurchaseItem has a global owner scope. Reports already explicitly
        | control owner_id, so using the query builder keeps this report
        | completely predictable and prevents the model's global scope from
        | interfering with joins/aggregations.
        |
        */

        $baseQuery = DB::table('purchase_items')
            ->where('purchase_items.owner_id', $ownerId)
            ->whereBetween(
                'purchase_items.created_at',
                [$summaryStart, $summaryEnd]
            );

        /*
        |--------------------------------------------------------------------------
        | SHOP FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('shop_id')) {
            $baseQuery->where(
                'purchase_items.shop_id',
                $request->shop_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL SALES
        |--------------------------------------------------------------------------
        */

        $totalSales = (clone $baseQuery)
            ->sum('purchase_items.total_price');

        /*
        |--------------------------------------------------------------------------
        | TOTAL TRANSACTIONS
        |--------------------------------------------------------------------------
        */

        $totalTransactions = (clone $baseQuery)
            ->whereNotNull('purchase_items.transaction_id')
            ->distinct()
            ->count('purchase_items.transaction_id');

        /*
        |--------------------------------------------------------------------------
        | TOP PRODUCTS
        |--------------------------------------------------------------------------
        */

        $topProducts = (clone $baseQuery)
            ->join(
                'products',
                'purchase_items.product_id',
                '=',
                'products.id'
            )
            ->select(
                'products.id',
                'products.name as product_name',
                DB::raw('SUM(purchase_items.quantity) as total_sold')
            )
            ->groupBy(
                'products.id',
                'products.name'
            )
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | DAILY SALES TREND
        |--------------------------------------------------------------------------
        */

        // If a date filter was supplied, use that same range for the chart.
        //
        // Otherwise show the last 7 days.

        if ($startDate || $endDate) {

            $chartStart = $summaryStart->copy()->startOfDay();
            $chartEnd   = $summaryEnd->copy()->endOfDay();

        } else {

            $chartStart = Carbon::today()
                ->subDays(6)
                ->startOfDay();

            $chartEnd = Carbon::today()
                ->endOfDay();
        }

        $chartQuery = DB::table('purchase_items')
            ->where('purchase_items.owner_id', $ownerId)
            ->whereBetween(
                'purchase_items.created_at',
                [$chartStart, $chartEnd]
            );

        if ($request->filled('shop_id')) {
            $chartQuery->where(
                'purchase_items.shop_id',
                $request->shop_id
            );
        }

        $dbSales = $chartQuery
            ->select(
                DB::raw('DATE(purchase_items.created_at) as date'),
                DB::raw('SUM(purchase_items.total_price) as total')
            )
            ->groupBy(DB::raw('DATE(purchase_items.created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $salesByDay = collect();

        $daysDiff = $chartStart->diffInDays($chartEnd);

        for ($i = 0; $i <= $daysDiff; $i++) {

            $date = $chartStart
                ->copy()
                ->addDays($i)
                ->toDateString();

            $row = $dbSales->get($date);

            $salesByDay->push([
                'date'  => $date,
                'total' => $row ? (float) $row->total : 0,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => true,

            'data' => [
                'shops' => $shops,

                'total_sales' => (float) $totalSales,

                'total_transactions' => (int) $totalTransactions,

                'top_products' => $topProducts,

                'sales_by_day' => $salesByDay,
            ],
        ]);
    }


    /**
     * GET /api/admin/reports/sales/pdf
     */
    public function downloadPdf(Request $request)
    {
        $user = auth()->user();

        $ownerId = $user->getOwnerId();

        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $shopId    = $request->shop_id;

        /*
        |--------------------------------------------------------------------------
        | BASE QUERY
        |--------------------------------------------------------------------------
        */

        $query = DB::table('purchase_items')
            ->join(
                'products',
                'purchase_items.product_id',
                '=',
                'products.id'
            )
            ->join(
                'shops',
                'purchase_items.shop_id',
                '=',
                'shops.id'
            )
            ->where(
                'purchase_items.owner_id',
                $ownerId
            );

        /*
        |--------------------------------------------------------------------------
        | DATE FILTER
        |--------------------------------------------------------------------------
        */

        if ($startDate && $endDate) {

            $query->whereBetween(
                'purchase_items.created_at',
                [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ]
            );

        } elseif ($startDate) {

            $query->where(
                'purchase_items.created_at',
                '>=',
                Carbon::parse($startDate)->startOfDay()
            );

        } elseif ($endDate) {

            $query->where(
                'purchase_items.created_at',
                '<=',
                Carbon::parse($endDate)->endOfDay()
            );

        } else {

            // Default PDF = today
            $query->whereBetween(
                'purchase_items.created_at',
                [
                    Carbon::today()->startOfDay(),
                    Carbon::today()->endOfDay(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SHOP FILTER
        |--------------------------------------------------------------------------
        */

        if ($shopId) {
            $query->where(
                'purchase_items.shop_id',
                $shopId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TOP PRODUCTS
        |--------------------------------------------------------------------------
        */

        $topProducts = (clone $query)
            ->select(
                'products.name as product_name',
                DB::raw('SUM(purchase_items.quantity) as total_sold')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | TOTAL SALES
        |--------------------------------------------------------------------------
        */

        $totalSales = (clone $query)
            ->sum('purchase_items.total_price');

        /*
        |--------------------------------------------------------------------------
        | TRANSACTIONS
        |--------------------------------------------------------------------------
        */

        $totalTransactions = (clone $query)
            ->whereNotNull('purchase_items.transaction_id')
            ->distinct()
            ->count('purchase_items.transaction_id');

        /*
        |--------------------------------------------------------------------------
        | SHOP
        |--------------------------------------------------------------------------
        */

        $shop = $shopId
            ? Shop::where('owner_id', $ownerId)->find($shopId)
            : null;

        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
            'admin.report.sales_report_pdf',
            compact(
                'topProducts',
                'totalSales',
                'totalTransactions',
                'startDate',
                'endDate',
                'shop'
            )
        )->setPaper('a4', 'portrait');

        return $pdf->download('sales_report.pdf');
    }
}