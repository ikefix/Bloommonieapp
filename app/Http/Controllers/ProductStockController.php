<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductStockController extends Controller
{
    /** Resolve the tenant owner. Adjust if yours works differently. */
    private function ownerId(): int
    {
        return (int) (auth()->user()->owner_id ?? auth()->id());
    }

    /**
     * ONE place that applies every filter + sort.
     * Used by both the page and the PDF download, so the PDF always
     * contains exactly what the person is looking at.
     */
    private function filteredQuery(Request $request, int $ownerId)
    {
        $query = Product::with(['category', 'shop'])
            ->select('products.*')
            ->where('products.owner_id', $ownerId)
            // total quantity ever sold
            ->selectSub(
                DB::table('purchase_items')
                    ->selectRaw('COALESCE(SUM(quantity), 0)')
                    ->whereColumn('purchase_items.product_id', 'products.id'),
                'total_sold'
            )
            // date of the last sale
            ->selectSub(
                DB::table('purchase_items')
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('purchase_items.product_id', 'products.id'),
                'last_sold_at'
            );

        // 🔎 Search (name or barcode)
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        // 🏪 Shop / 🗂 Category
        if ($request->filled('shop_id')) {
            $query->where('products.shop_id', $request->shop_id);
        }
        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }

        // 📅 Date added range
        if ($request->filled('from')) {
            $query->whereDate('products.created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('products.created_at', '<=', $request->to);
        }

        // 📦 Stock status
        switch ($request->input('status')) {
            case 'out':
                $query->where('products.stock_quantity', '<=', 0);
                break;
            case 'low':
                $query->where('products.stock_quantity', '>', 0)
                      ->whereNotNull('products.stock_limit')
                      ->where('products.stock_limit', '>', 0)
                      ->whereColumn('products.stock_quantity', '<=', 'products.stock_limit');
                break;
            case 'in':
                $query->where('products.stock_quantity', '>', 0)
                      ->where(function ($q) {
                          $q->whereNull('products.stock_limit')
                            ->orWhere('products.stock_limit', '<=', 0)
                            ->orWhereColumn('products.stock_quantity', '>', 'products.stock_limit');
                      });
                break;
        }

        // ↕ Sorting
        $sorts = [
            'newest'     => ['products.created_at', 'desc'],
            'oldest'     => ['products.created_at', 'asc'],
            'updated'    => ['products.updated_at', 'desc'],
            'stock_low'  => ['products.stock_quantity', 'asc'],
            'stock_high' => ['products.stock_quantity', 'desc'],
            'name'       => ['products.name', 'asc'],
        ];
        [$col, $dir] = $sorts[$request->input('sort', 'newest')] ?? $sorts['newest'];

        return $query->orderBy($col, $dir);
    }

    public function index(Request $request)
    {
        $ownerId = $this->ownerId();

        $products = $this->filteredQuery($request, $ownerId)->paginate(20)->withQueryString();

        // 📊 Summary cards (always for the whole owner, not just the filtered page)
        $base = Product::where('owner_id', $ownerId);
        $summary = [
            'total' => (clone $base)->count(),
            'out'   => (clone $base)->where('stock_quantity', '<=', 0)->count(),
            'low'   => (clone $base)->where('stock_quantity', '>', 0)
                            ->whereNotNull('stock_limit')->where('stock_limit', '>', 0)
                            ->whereColumn('stock_quantity', '<=', 'stock_limit')->count(),
            'value' => (clone $base)->where('stock_quantity', '>', 0)
                            ->sum(DB::raw('stock_quantity * cost_price')),
        ];

        $shops      = Shop::where('owner_id', $ownerId)->orderBy('name')->get();
        $categories = Category::where('owner_id', $ownerId)->orderBy('name')->get();

        return view('inventory.products', compact('products', 'summary', 'shops', 'categories'));
    }

    /**
     * Download the PDF.
     * - No filters  -> every product the owner has
     * - Filters set -> only the matching products (all pages, not just page 1)
     */
    public function download(Request $request)
    {
        $ownerId  = $this->ownerId();
        $products = $this->filteredQuery($request, $ownerId)->get();

        // Human-readable list of what was applied (printed at the top of the PDF)
        $filters = [];

        if ($s = trim($request->input('search', ''))) {
            $filters[] = "Search: \"{$s}\"";
        }
        if ($request->filled('shop_id')) {
            $name = Shop::where('owner_id', $ownerId)->find($request->shop_id)?->name;
            if ($name) $filters[] = "Shop: {$name}";
        }
        if ($request->filled('category_id')) {
            $name = Category::where('owner_id', $ownerId)->find($request->category_id)?->name;
            if ($name) $filters[] = "Category: {$name}";
        }
        if ($request->filled('status')) {
            $labels = ['in' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'];
            if (isset($labels[$request->status])) $filters[] = 'Status: ' . $labels[$request->status];
        }
        if ($request->filled('from')) {
            $filters[] = 'Added from: ' . date('d M Y', strtotime($request->from));
        }
        if ($request->filled('to')) {
            $filters[] = 'Added to: ' . date('d M Y', strtotime($request->to));
        }

        $sortLabels = [
            'newest' => 'Newest added', 'oldest' => 'Oldest added', 'updated' => 'Recently updated',
            'stock_low' => 'Lowest stock', 'stock_high' => 'Highest stock', 'name' => 'Name A–Z',
        ];
        $sortLabel = $sortLabels[$request->input('sort', 'newest')] ?? 'Newest added';

        // Totals for just the products in this PDF
        $isLow = fn ($p) => $p->stock_quantity > 0 && ($p->stock_limit ?? 0) > 0 && $p->stock_quantity <= $p->stock_limit;
        $summary = [
            'total' => $products->count(),
            'out'   => $products->filter(fn ($p) => $p->stock_quantity <= 0)->count(),
            'low'   => $products->filter($isLow)->count(),
            'value' => $products->filter(fn ($p) => $p->stock_quantity > 0)
                                ->sum(fn ($p) => $p->stock_quantity * $p->cost_price),
        ];

        $pdf = Pdf::loadView('inventory.products_pdf', [
            'products'    => $products,
            'filters'     => $filters,
            'sortLabel'   => $sortLabel,
            'summary'     => $summary,
            'generatedAt' => now(),
            'businessName'=> config('app.name', 'Bloommonie'),
        ])->setPaper('a4', 'landscape');

        // Render once, then stamp "Page X of Y" on every page
        $pdf->output();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $canvas->page_text(
            770, 572, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal'),
            8, [0.45, 0.45, 0.45]
        );

        $filename = 'product-stock-' . ($filters ? 'filtered-' : 'all-') . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }
}