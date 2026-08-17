<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PurchaseItem;
use App\Models\Expense;
use App\Models\Shop;
use App\Models\Category;
use Barryvdh\DomPDF\Facade\Pdf;

class ProfitReportController extends Controller
{
    // GET /api/admin/reports/profit-loss?start_date=&end_date=&shop_id=
    public function profitLoss(Request $request)
    {
        $shopId = $request->shop_id;

        /* ---------------- FILTER DATES ---------------- */
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfDay();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        /* ---------------- SALES ---------------- */
        $sales = PurchaseItem::whereBetween('created_at', [$startDate, $endDate])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->with('product')
            ->get();

        /* ---------------- TOTAL REVENUE ---------------- */
        if ($request->start_date && $request->end_date) {
            $totalRevenue = $sales->sum(fn($i) => $i->total_price - ($i->discount_value ?? 0));
        } else {
            $totalRevenue = PurchaseItem::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
                ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                ->get()
                ->sum(fn($i) => $i->total_price - ($i->discount_value ?? 0));
        }

        /* ---------------- TOTAL COST ---------------- */
        $totalCost = $sales->sum(fn ($i) => ($i->product->cost_price ?? 0) * $i->quantity);

        $grossProfit = $totalRevenue - $totalCost;

        /* ---------------- EXPENSES ---------------- */
        $expensesQuery = Expense::whereBetween('date', [$startDate, $endDate])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId));

        $totalExpenses = $expensesQuery->sum('amount');

        $expensesByCategory = $expensesQuery
            ->selectRaw('title, SUM(amount) as total')
            ->groupBy('title')
            ->get();

        /* ---------------- NET PROFIT ---------------- */
        $netProfit = $grossProfit - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        /* ---------------- CHART DATA ---------------- */
        if ($request->start_date && $request->end_date) {
            $chartStart = Carbon::parse($request->start_date)->startOfDay();
            $chartEnd = Carbon::parse($request->end_date)->endOfDay();
        } else {
            $chartStart = now()->subDays(9)->startOfDay(); // last 10 days
            $chartEnd = now()->endOfDay();
        }

        // Build period array
        $period = [];
        $current = $chartStart->copy();
        while ($current <= $chartEnd) {
            $period[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Group sales and expenses by date
        $rawSales = $sales->groupBy(fn($item) => $item->created_at->format('Y-m-d'));
        $rawExpenses = Expense::whereBetween('date', [$chartStart, $chartEnd])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        // Map profit by day
        $profitByDay = collect($period)->map(function ($date) use ($rawSales, $rawExpenses) {
            $items = $rawSales->get($date, collect());
            $expenses = $rawExpenses->get($date, collect());

            $revenue = $items->sum(fn($i) => $i->total_price - ($i->discount_value ?? 0));
            $cost = $items->sum(fn($i) => ($i->product->cost_price ?? 0) * $i->quantity);
            $expenseTotal = $expenses->sum('amount');

            return [
                'date' => $date,
                'revenue' => $revenue,
                'expenses' => $expenseTotal,
                'profit' => $revenue - $cost - $expenseTotal,
            ];
        });

        /* ---------------- BEST & WORST DAY ---------------- */
        $bestDay = $profitByDay->sortByDesc('profit')->first();
        $worstDay = $profitByDay->sortBy('profit')->first();

        /* ---------------- SHOPS ---------------- */
        $shops = Shop::all();

        return response()->json([
            'status' => true,
            'data' => [
                'total_revenue'        => $totalRevenue,
                'total_cost'           => $totalCost,
                'gross_profit'         => $grossProfit,
                'total_expenses'       => $totalExpenses,
                'net_profit'           => $netProfit,
                'profit_margin'        => $profitMargin,
                'profit_by_day'        => $profitByDay->values(),
                'expenses_by_category' => $expensesByCategory,
                'best_day'             => $bestDay,
                'worst_day'            => $worstDay,
                'shops'                => $shops,
                'sales'                => $sales,
            ],
        ]);
    }

    // GET /api/admin/reports/profit-loss/goods-pdf?start_date=&end_date=&shop_id=
    // Unchanged — still returns a binary PDF file.
    public function downloadProfitGoodsPdf(Request $request)
    {
        $sales = PurchaseItem::with('product')
            ->when($request->start_date, fn ($q) =>
                $q->whereDate('created_at', '>=', $request->start_date)
            )
            ->when($request->end_date, fn ($q) =>
                $q->whereDate('created_at', '<=', $request->end_date)
            )
            ->when($request->shop_id, fn ($q) =>
                $q->where('shop_id', $request->shop_id)
            )
            ->get();

        $goodsByProfit = $sales
            ->groupBy(fn ($item) => $item->product->name)
            ->map(fn ($items, $name) => [
                'product'  => $name,
                'quantity' => $items->sum('quantity'),
                'revenue'  => $items->sum(fn ($i) =>
                    $i->total_price - ($i->discount_value ?? 0)
                ),
                'cost'     => $items->sum(fn ($i) =>
                    ($i->product->cost_price ?? 0) * $i->quantity
                ),
            ])
            ->map(fn ($item) => array_merge($item, [
                'profit' => $item['revenue'] - $item['cost']
            ]))
            ->filter(fn ($item) => $item['profit'] > 0);

        $pdf = Pdf::loadView(
            'admin.report.pdf.goods_profit',
            compact('goodsByProfit', 'request')
        );

        return $pdf->download('goods-that-made-profit.pdf');
    }
}