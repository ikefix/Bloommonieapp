<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Production;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ProductionType;
use App\Models\ProductionEntry;
use Carbon\Carbon;

class ProductionReportController extends Controller
{
    public function productionReport(Request $request)
    {
    
        $shops      = Shop::all();
        $startDate  = $request->start_date;
        $endDate    = $request->end_date;
        $shopId     = $request->shop_id;
        $search     = $request->search;

        $query = Production::with(['entries', 'productionType', 'shop'])

            ->when($startDate, fn($q) =>
                $q->whereDate('start_date', '>=', $startDate)
            )

            ->when($endDate, fn($q) =>
                $q->whereDate('end_date', '<=', $endDate)
            )

            ->when($shopId, fn($q) =>
                $q->where('shop_id', $shopId)
            )

            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {

                    $sub->where('batch_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");

                });
            })

            ->latest();

        $productions = $query->get();


        // ── Summary ───────────────────────────────────────────────────────────
        $totalProductions  = $productions->count();
        $completedCount    = $productions->where('status', 'completed')->count();
        $inProgressCount   = $productions->where('status', 'in_progress')->count();
        $pendingCount      = $productions->whereNotIn('status', ['completed', 'in_progress'])->count();

        // ── Totals from meta ──────────────────────────────────────────────────
        $totalInputCost    = 0;
        $totalOutputValue  = 0;
        $totalLossValue    = 0;
        $inputItemsAll     = [];
        $outputItemsAll    = [];
        $lossItemsAll      = [];

        foreach ($productions as $production) {

            $inputEntry  = $production->entries->where('entry_type', 'input')->first();
            $outputEntry = $production->entries->where('entry_type', 'output')->first();
            $lossEntry   = $production->entries->where('entry_type', 'loss')->first();

            $getItems = function($entry) {
                if (!$entry) return [];
                $meta  = is_string($entry->meta) ? json_decode($entry->meta, true) : $entry->meta;
                return array_filter($meta['items'] ?? [], fn($i) => is_array($i));
            };

            $inputs  = $getItems($inputEntry);
            $outputs = $getItems($outputEntry);
            $losses  = $getItems($lossEntry);

            foreach ($inputs  as $i) { $totalInputCost   += (float)($i['price'] ?? 0); }
            foreach ($outputs as $o) { $totalOutputValue += (float)($o['price'] ?? 0); }
            foreach ($losses  as $l) { $totalLossValue   += (float)($l['price'] ?? 0); }

            $inputItemsAll  = array_merge($inputItemsAll,  array_values($inputs));
            $outputItemsAll = array_merge($outputItemsAll, array_values($outputs));
            $lossItemsAll   = array_merge($lossItemsAll,   array_values($losses));

            // Attach to production for the table
            $production->parsed_inputs  = array_values($inputs);
            $production->parsed_outputs = array_values($outputs);
            $production->parsed_losses  = array_values($losses);
        }

        $netValue = $totalOutputValue - $totalInputCost - $totalLossValue;

        // ── Top output products ───────────────────────────────────────────────
        $outputTotals = [];
        foreach ($outputItemsAll as $item) {
            $name = $item['item_name'] ?? 'Unknown';
            if (!isset($outputTotals[$name])) $outputTotals[$name] = 0;
            $outputTotals[$name] += (float)($item['quantity'] ?? 0);
        }
        arsort($outputTotals);

        // ── Productions by type ───────────────────────────────────────────────
        $byType = $productions->groupBy(fn($p) => $p->productionType->name ?? 'Unknown')
                            ->map->count();

        $chartLabels = [];
        $chartCounts = [];

        for ($i = 15; $i >= 0; $i--) {

            $date = Carbon::today()->subDays($i)->format('Y-m-d');

            $chartLabels[] = $date;

            $chartCounts[] = $productions
                ->filter(function ($p) use ($date) {
                    return Carbon::parse($p->start_date)->format('Y-m-d') === $date;
                })
                ->count();
        }

        return view('admin.report.production_report', compact(
            'productions', 'shops',
            'totalProductions', 'completedCount', 'inProgressCount', 'pendingCount',
            'totalInputCost', 'totalOutputValue', 'totalLossValue', 'netValue',
            'outputTotals', 'byType',
            'startDate', 'endDate', 'shopId', 'chartLabels', 'chartCounts', 'search'
        ));
    }

    private function getProductionReportData(Request $request)
{
    $shops      = Shop::all();
    $startDate  = $request->start_date;
    $endDate    = $request->end_date;
    $shopId     = $request->shop_id;

    $query = Production::with(['entries', 'productionType', 'shop'])
        ->when($startDate, fn($q) => $q->whereDate('start_date', '>=', $startDate))
        ->when($endDate, fn($q) => $q->whereDate('end_date', '<=', $endDate))
        ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
        ->latest();

    $productions = $query->get();

    $productNames = Product::pluck('name', 'id')->toArray();

    // SUMMARY
    $totalProductions = $productions->count();
    $completedCount   = $productions->where('status', 'completed')->count();
    $inProgressCount  = $productions->where('status', 'in_progress')->count();
    $pendingCount     = $productions->whereNotIn('status', ['completed', 'in_progress'])->count();

    $totalInputCost   = 0;
    $totalOutputValue = 0;
    $totalLossValue   = 0;

    $inputItemsAll  = [];
    $outputItemsAll = [];
    $lossItemsAll   = [];

    foreach ($productions as $production) {

        $inputEntry  = $production->entries->where('entry_type', 'input')->first();
        $outputEntry = $production->entries->where('entry_type', 'output')->first();
        $lossEntry   = $production->entries->where('entry_type', 'loss')->first();

        $getItems = function ($entry, $type = null) use ($productNames) {

            if (!$entry) {
                return [];
            }

            $meta = is_string($entry->meta)
                ? json_decode($entry->meta, true)
                : $entry->meta;

            if (!is_array($meta)) {
                return [];
            }

            // NEW STRUCTURE
            if ($type && isset($meta[$type])) {
                $items = $meta[$type];
            }

            // OLD STRUCTURE
            elseif (isset($meta['items'])) {
                $items = $meta['items'];
            }

            else {
                $items = [];
            }

            $items = array_filter($items, fn($i) => is_array($i));

            foreach ($items as &$item) {

                if (!empty($item['item_id'])) {
                    $item['product_name'] =
                        $productNames[$item['item_id']]
                        ?? 'Unknown Product';
                }
            }

            return array_values($items);
        };

        $inputs  = $getItems($inputEntry, 'input');
        $outputs = $getItems($outputEntry, 'output');
        $losses  = $getItems($lossEntry, 'loss');

        foreach ($inputs as $i) {
            $totalInputCost += (float)($i['price'] ?? 0);
        }

        foreach ($outputs as $o) {
            $totalOutputValue += (float)($o['price'] ?? 0);
        }

        foreach ($losses as $l) {
            $totalLossValue += (float)($l['price'] ?? 0);
        }

        $inputItemsAll  = array_merge($inputItemsAll, $inputs);
        $outputItemsAll = array_merge($outputItemsAll, $outputs);
        $lossItemsAll   = array_merge($lossItemsAll, $losses);

        $production->parsed_inputs  = $inputs;
        $production->parsed_outputs = $outputs;
        $production->parsed_losses  = $losses;
    }

    $netValue = $totalOutputValue - $totalInputCost - $totalLossValue;

    // TOP OUTPUT PRODUCTS
    $outputTotals = [];

    foreach ($outputItemsAll as $item) {

        if (
            empty($item['item_name']) &&
            empty($item['quantity'])
        ) {
            continue;
        }

        $name = $item['item_name'] ?? 'Unknown';

        if (!isset($outputTotals[$name])) {
            $outputTotals[$name] = 0;
        }

        $outputTotals[$name] += (float)($item['quantity'] ?? 0);
    }

    arsort($outputTotals);

    // PRODUCTIONS BY TYPE
    $byType = $productions
        ->groupBy(fn($p) => $p->productionType->name ?? 'Unknown')
        ->map
        ->count();

    return [
        'productions'      => $productions,
        'shops'            => $shops,
        'startDate'        => $startDate,
        'endDate'          => $endDate,
        'shopId'           => $shopId,

        'totalProductions' => $totalProductions,
        'completedCount'   => $completedCount,
        'inProgressCount'  => $inProgressCount,
        'pendingCount'     => $pendingCount,

        'totalInputCost'   => $totalInputCost,
        'totalOutputValue' => $totalOutputValue,
        'totalLossValue'   => $totalLossValue,
        'netValue'         => $netValue,

        'outputTotals'     => $outputTotals,
        'byType'           => $byType,
    ];
}

public function productionReportPdf(Request $request)
{
    $pdf = \PDF::loadView(
        'admin.report.pdf.production_report_pdf',
        $this->getProductionReportData($request)
    );

    return $pdf->download('production-report.pdf');
}
}
