<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductStockController extends Controller
{
    /**
     * Resolve authenticated tenant owner.
     */
    private function ownerId(): int
    {
        return (int) (auth()->user()->owner_id ?? auth()->id());
    }

    /**
     * Validate filter parameters.
     */
    private function validateFilters(Request $request): void
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'shop_id' => [
                'nullable',
                'integer',
                Rule::exists('shops', 'id')
                    ->where('owner_id', $this->ownerId()),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('owner_id', $this->ownerId()),
            ],
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'status' => 'nullable|in:in,low,out',
            'sort' => 'nullable|in:newest,oldest,updated,stock_low,stock_high,name',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    }

    /**
     * Apply all filters and sorting.
     */
    private function filteredQuery(Request $request, int $ownerId)
    {
        $query = Product::with(['category', 'shop'])
            ->select('products.*')
            ->where('products.owner_id', $ownerId)

            // Total quantity ever sold.
            ->selectSub(
                DB::table('purchase_items')
                    ->selectRaw('COALESCE(SUM(quantity), 0)')
                    ->whereColumn(
                        'purchase_items.product_id',
                        'products.id'
                    ),
                'total_sold'
            )

            // Last sale date.
            ->selectSub(
                DB::table('purchase_items')
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn(
                        'purchase_items.product_id',
                        'products.id'
                    ),
                'last_sold_at'
            );

        // Search.
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        // Shop filter.
        if ($request->filled('shop_id')) {
            $query->where('products.shop_id', $request->shop_id);
        }

        // Category filter.
        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }

        // Date range.
        if ($request->filled('from')) {
            $query->whereDate(
                'products.created_at',
                '>=',
                $request->from
            );
        }

        if ($request->filled('to')) {
            $query->whereDate(
                'products.created_at',
                '<=',
                $request->to
            );
        }

        // Stock status.
        switch ($request->input('status')) {
            case 'out':
                $query->where('products.stock_quantity', '<=', 0);
                break;

            case 'low':
                $query->where('products.stock_quantity', '>', 0)
                    ->whereNotNull('products.stock_limit')
                    ->where('products.stock_limit', '>', 0)
                    ->whereColumn(
                        'products.stock_quantity',
                        '<=',
                        'products.stock_limit'
                    );
                break;

            case 'in':
                $query->where('products.stock_quantity', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('products.stock_limit')
                            ->orWhere('products.stock_limit', '<=', 0)
                            ->orWhereColumn(
                                'products.stock_quantity',
                                '>',
                                'products.stock_limit'
                            );
                    });
                break;
        }

        // Sorting.
        $sorts = [
            'newest' => ['products.created_at', 'desc'],
            'oldest' => ['products.created_at', 'asc'],
            'updated' => ['products.updated_at', 'desc'],
            'stock_low' => ['products.stock_quantity', 'asc'],
            'stock_high' => ['products.stock_quantity', 'desc'],
            'name' => ['products.name', 'asc'],
        ];

        [$column, $direction] = $sorts[
            $request->input('sort', 'newest')
        ];

        return $query->orderBy($column, $direction);
    }

    /**
     * Inventory summary.
     */
    private function inventorySummary(int $ownerId): array
    {
        $base = Product::where('owner_id', $ownerId);

        return [
            'total' => (clone $base)->count(),

            'out' => (clone $base)
                ->where('stock_quantity', '<=', 0)
                ->count(),

            'low' => (clone $base)
                ->where('stock_quantity', '>', 0)
                ->whereNotNull('stock_limit')
                ->where('stock_limit', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'stock_limit')
                ->count(),

            'value' => (clone $base)
                ->where('stock_quantity', '>', 0)
                ->sum(DB::raw('stock_quantity * cost_price')),
        ];
    }

    /**
     * GET /api/product-stock
     *
     * Fetch inventory with filters and pagination.
     */
    public function index(Request $request)
    {
        $this->validateFilters($request);

        $ownerId = $this->ownerId();

        $products = $this->filteredQuery($request, $ownerId)
            ->paginate($request->input('per_page', 20))
            ->withQueryString();

        $shops = Shop::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = Category::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'message' => 'Inventory retrieved successfully.',

            'summary' => $this->inventorySummary($ownerId),

            'filters' => [
                'search' => $request->search,
                'shop_id' => $request->shop_id,
                'category_id' => $request->category_id,
                'from' => $request->from,
                'to' => $request->to,
                'status' => $request->status,
                'sort' => $request->input('sort', 'newest'),
            ],

            'shops' => $shops,
            'categories' => $categories,

            'products' => $products,
        ]);
    }

    /**
     * GET /api/product-stock/download
     *
     * Download inventory PDF.
     */
    public function download(Request $request)
    {
        $this->validateFilters($request);

        $ownerId = $this->ownerId();

        // Get all matching products, not just the current page.
        $products = $this->filteredQuery($request, $ownerId)->get();

        $filters = [];

        if ($search = trim($request->input('search', ''))) {
            $filters[] = "Search: \"{$search}\"";
        }

        if ($request->filled('shop_id')) {
            $name = Shop::where('owner_id', $ownerId)
                ->find($request->shop_id)?->name;

            if ($name) {
                $filters[] = "Shop: {$name}";
            }
        }

        if ($request->filled('category_id')) {
            $name = Category::where('owner_id', $ownerId)
                ->find($request->category_id)?->name;

            if ($name) {
                $filters[] = "Category: {$name}";
            }
        }

        if ($request->filled('status')) {
            $labels = [
                'in' => 'In stock',
                'low' => 'Low stock',
                'out' => 'Out of stock',
            ];

            $filters[] = 'Status: ' . $labels[$request->status];
        }

        if ($request->filled('from')) {
            $filters[] = 'Added from: ' .
                date('d M Y', strtotime($request->from));
        }

        if ($request->filled('to')) {
            $filters[] = 'Added to: ' .
                date('d M Y', strtotime($request->to));
        }

        $sortLabels = [
            'newest' => 'Newest added',
            'oldest' => 'Oldest added',
            'updated' => 'Recently updated',
            'stock_low' => 'Lowest stock',
            'stock_high' => 'Highest stock',
            'name' => 'Name A–Z',
        ];

        $sortLabel = $sortLabels[
            $request->input('sort', 'newest')
        ];

        // PDF summary.
        $isLow = fn ($p) =>
            $p->stock_quantity > 0 &&
            ($p->stock_limit ?? 0) > 0 &&
            $p->stock_quantity <= $p->stock_limit;

        $summary = [
            'total' => $products->count(),

            'out' => $products
                ->filter(fn ($p) => $p->stock_quantity <= 0)
                ->count(),

            'low' => $products->filter($isLow)->count(),

            'value' => $products
                ->filter(fn ($p) => $p->stock_quantity > 0)
                ->sum(fn ($p) =>
                    $p->stock_quantity * $p->cost_price
                ),
        ];

        $pdf = Pdf::loadView('inventory.products_pdf', [
            'products' => $products,
            'filters' => $filters,
            'sortLabel' => $sortLabel,
            'summary' => $summary,
            'generatedAt' => now(),
            'businessName' => config('app.name', 'Bloommonie'),
        ])->setPaper('a4', 'landscape');

        // Add page numbers.
        $pdf->output();

        $canvas = $pdf->getDomPDF()->getCanvas();

        $canvas->page_text(
            770,
            572,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $pdf->getDomPDF()
                ->getFontMetrics()
                ->getFont('DejaVu Sans', 'normal'),
            8,
            [0.45, 0.45, 0.45]
        );

        $filename = 'product-stock-' .
            ($filters ? 'filtered-' : 'all-') .
            now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }
}
