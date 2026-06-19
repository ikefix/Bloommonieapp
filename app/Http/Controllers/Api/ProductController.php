<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\Shop;
use App\Models\Notification;
use App\Models\PurchaseItem;

use App\Notifications\LowStockAlert;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $products = Product::where('shop_id', $user->shop_id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    // IMPORT PRODUCTS
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,csv'
        ]);

        Excel::import(new ProductsImport, $request->file('excel_file'));

        return response()->json([
            'status' => true,
            'message' => 'Products imported successfully 🚀'
        ]);
    }

    // CREATE PRODUCT
    public function store(Request $request)
    {
        $user = Auth::user();

        // AUTH CHECK
        if ($user->role === 'manager') {
            $hasPermission = \App\Models\ProductPermission::where(
                'manager_id',
                $user->id
            )->exists();

            if (!$hasPermission) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to add products.'
                ], 403);
            }
        } elseif ($user->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        if (!auth()->user()->canCreateMoreProducts()) {
            return response()->json([
                'status' => false,
                'message' => 'Product limit reached for your plan.'
            ], 403);
        }

        // VALIDATION
        $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'shop_id'        => 'required|exists:shops,id',
            'name'           => 'required|string|max:255',
            'barcode'        => 'required|string|unique:products,barcode',
            'price'          => 'required|numeric|min:0',
            'cost_price'     => 'required|numeric|min:0',
            'stock_limit'    => 'required|numeric|min:0',
            'stock_quantity' => 'required|numeric|min:0',
            'stock_unit'     => 'nullable|string|max:50',
            'unit_size'      => 'nullable|numeric|min:0',
        ]);

        // DUPLICATE CHECK
        $existingProduct = Product::where('name', $request->name)
            ->where('shop_id', $request->shop_id)
            ->where('category_id', $request->category_id)
            ->first();

        if ($existingProduct) {
            return response()->json([
                'status' => false,
                'message' => 'Product already exists in this shop and category.'
            ], 409);
        }

        $product = Product::create($request->all());

        $this->checkStockNotification($product);

        return response()->json([
            'status' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ]);
    }

    // SHOW PRODUCT
    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $product
        ]);
    }

    // UPDATE PRODUCT
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'shop_id'        => 'required|exists:shops,id',
            'name'           => 'required|string|max:255',
            'price'          => 'required|numeric|min:0',
            'cost_price'     => 'required|numeric|min:0',
            'stock_quantity' => 'required|numeric|min:0',
        ]);

        if ($request->stock_quantity < 0) {
            return response()->json([
                'status' => false,
                'message' => 'Stock quantity cannot be negative'
            ], 422);
        }

        $product->update($request->all());

        $this->checkStockNotification($product);

        return response()->json([
            'status' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    // DELETE PRODUCT
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    // SEARCH SUGGESTIONS
    public function searchSuggestions(Request $request)
    {
        $query = strtolower(trim($request->input('query')));
        $shopId = auth()->user()->shop_id ?? null;

        if (!$query) {
            return response()->json([
                'status' => true,
                'data' => []
            ]);
        }

        $products = Product::where('shop_id', $shopId)
            ->where(function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
                  ->orWhere('barcode', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'price', 'barcode']);

        $exact = Product::where('shop_id', $shopId)
            ->where('barcode', $query)
            ->first(['id', 'name', 'price', 'barcode']);

        if ($exact) {
            return response()->json([
                'status' => true,
                'data' => $exact
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    // SELL PRODUCT
    public function sellProduct(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
        ]);

        $product = Product::findOrFail($productId);

        if ($product->stock_quantity < $request->quantity) {
            return response()->json([
                'status' => false,
                'message' => 'Not enough stock available'
            ], 400);
        }

        $product->stock_quantity -= $request->quantity;
        $product->save();

        $transactionId = 'TXN-' . now()->format('YmdHis') . '-' . rand(1000, 9999);

        $purchase = PurchaseItem::create([
            'product_id'     => $product->id,
            'category_id'    => $product->category_id,
            'quantity'       => $request->quantity,
            'total_price'    => $product->price * $request->quantity,
            'payment_method' => $request->payment_method,
            'transaction_id' => $transactionId,
            'shop_id'        => auth()->user()->shop_id,
        ]);

        $this->updateStockAndNotify($product, $request->quantity);

        return response()->json([
            'status' => true,
            'message' => 'Sale successful',
            'data' => $purchase
        ]);
    }

    // STOCK CHECK
    public function getStock($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'stock' => $product->stock_quantity
        ]);
    }

    // LOW STOCK ALERT
    private function checkStockNotification(Product $product)
    {
        if ($product->stock_quantity <= $product->stock_limit) {
            $admin = User::whereIn('role', ['admin', 'manager'])->first();

            if ($admin) {
                $admin->notify(new LowStockAlert($product));
            }
        }
    }

    private function updateStockAndNotify(Product $product, $quantitySold)
    {
        if ($product->stock_quantity <= $product->stock_limit) {
            $admin = User::whereIn('role', ['admin', 'manager'])->first();

            if ($admin) {
                $admin->notify(new LowStockAlert($product));
            }
        }

        if ($product->stock_quantity <= 0) {
            $product->delete();
        }
    }
}