<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductStockController extends Controller
{
    public function index(Request $request)
    {
        // Adjust if your tenant owner is resolved differently
        $ownerId = auth()->user()->owner_id ?? auth()->id();

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
            'newest'    => ['products.created_at', 'desc'],
            'oldest'    => ['products.created_at', 'asc'],
            'updated'   => ['products.updated_at', 'desc'],
            'stock_low' => ['products.stock_quantity', 'asc'],
            'stock_high'=> ['products.stock_quantity', 'desc'],
            'name'      => ['products.name', 'asc'],
        ];
        [$col, $dir] = $sorts[$request->input('sort', 'newest')] ?? $sorts['newest'];
        $query->orderBy($col, $dir);

        $products = $query->paginate(20)->withQueryString();

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
}