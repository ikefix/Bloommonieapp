<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

use App\Notifications\LowStockAlert;

class PurchaseItemController extends Controller
{
    // Get the cashier page with categories and products
    public function index()
    {
        // Fetch all categories with associated products
        $categories = Category::with('products')->get();

        // Pass categories to the view
        return view('home', compact('categories'));
    }

    // Get products based on the selected category (AJAX)a
    public function getProductsByCategory($categoryId)
    {
        $products = Product::where('category_id', $categoryId)->get();

        return response()->json($products);
    }
public function searchReceipt(Request $request)
{
    $request->validate([
        'transaction_id' => 'required|string'
    ]);

    $transactionId = $request->transaction_id;

    $items = PurchaseItem::with(['product', 'shop'])
        ->where('transaction_id', $transactionId)
        ->get();

    if ($items->isEmpty()) {
        return back()->with('error', 'Transaction not found');
    }

    $total = $items->sum('total_price');
    $cashier = auth()->check() ? auth()->user()->name : 'Unknown Cashier';
    $shopName = $items->first()->shop ? $items->first()->shop->name : 'Unknown Shop';

    return view('receipts.receipt', compact('items', 'total', 'cashier', 'shopName', 'transactionId'));
}


// Store the purchase item and update stock
public function store(Request $request)
{
    try {
        $validated = $request->validate([
            // 🧍 Customer info (optional)
            'customer_name'  => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',

            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.discount_type' => 'nullable|in:none,percentage,flat',
            'products.*.discount_value' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,transfer',
        ]);

        $transactionId = 'TXN-' . now()->format('YmdHis') . '-' . rand(1000, 9999);
        $lastPurchase = null;

        foreach ($validated['products'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            $quantityRequested = $item['quantity'];

            if ($product->stock_quantity < $quantityRequested) {
                return response()->json([
                    'success' => false,
                    'message' => "Not enough stock for {$product->name}. Available: {$product->stock_quantity}"
                ], 400);
            }

            // 🧮 Calculate discount properly
            $discountType = $item['discount_type'] ?? 'none';
            $discountValue = $item['discount_value'] ?? 0;
            $priceBeforeDiscount = $product->price * $quantityRequested;
            $discountAmount = 0;

            if ($discountType === 'percentage') {
                $discountAmount = ($discountValue / 100) * $priceBeforeDiscount;
            } elseif ($discountType === 'flat') {
                $discountAmount = $discountValue;
            }

            $totalAfterDiscount = max($priceBeforeDiscount - $discountAmount, 0);

            $lastPurchase = PurchaseItem::create([
                // 🧍 Customer info
                'customer_name'  => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'product_id'     => $product->id,
                'category_id'    => $product->category_id,
                'quantity'       => $quantityRequested,
                'total_price'    => $totalAfterDiscount,
                'discount'       => $discountAmount,
                'discount_type'  => $discountType,
                'discount_value' => $discountValue,
                'payment_method' => $validated['payment_method'],
                'transaction_id' => $transactionId,
                'shop_id'        => $product->shop_id,
                'cashier_id'     => auth()->id(),
                'owner_id' => auth()->user()->owner_id,
            ]);

            // 🔄 Update stock
            $product->decrement('stock_quantity', $quantityRequested);

            // ⚠️ Low stock alert
            if ($product->stock_quantity <= $product->stock_limit) {
                $admins = User::whereIn('role', ['admin', 'manager'])->get();
                Notification::send($admins, new LowStockAlert($product));
            }
        }

        return response()->json([
            'success'    => true,
            'receipt_id' => $lastPurchase->id,
            'txn_id'     => $transactionId
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage(),
        ], 500);
    }
}




    // View all sales with search and date filtering FOR ADMIN
    public function allSales(Request $request)
    {
        $search = $request->input('search');
        $date = $request->input('date', now()->toDateString()); // 👈 Default to today

        $sales = PurchaseItem::with(['product.category', 'shop'])
            ->when($search, function ($query, $search) {
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();
        

        
            $shops = Shop::all();

        return view('admin.sales', compact('sales', 'search', 'date', 'shops'));
    }


public function cashiersales(Request $request)
{
    $user = auth()->user();

    $search     = $request->input('search');
    $startDate  = $request->input('start_date');
    $endDate    = $request->input('end_date');
    $quick      = $request->input('quick');

    // If no filter at all → DEFAULT TO TODAY
    if (!$startDate && !$endDate && !$quick) {
        $startDate = $endDate = now()->toDateString();
    }

    // QUICK FILTERS
    if ($quick === 'today') {
        $startDate = $endDate = now()->toDateString();
    }
    if ($quick === 'yesterday') {
        $startDate = $endDate = now()->subDay()->toDateString();
    }
    if ($quick === 'week') {
        $startDate = now()->startOfWeek()->toDateString();
        $endDate   = now()->endOfWeek()->toDateString();
    }
    if ($quick === 'month') {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate   = now()->endOfMonth()->toDateString();
    }

    $sales = PurchaseItem::with(['product.category', 'shop'])
        ->when($search, function ($query, $search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        })
        ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59'
            ]);
        })
        ->where('shop_id', $user->shop_id)
        ->where('cashier_id', $user->id)   // 👈 NEW LINE (restrict to logged-in cashier)
        ->orderBy('created_at', 'desc')
        ->get();

    if (auth()->user()->role === 'cashier') {
        return view('cashier.home-sales', compact('sales'));
    }

    return view('receipts.receipt', compact('sales'));

}


        // View all sales FOR MANAGER
        public function managersales(Request $request)
{
    $search = $request->input('search');
    $date = $request->input('date', now()->toDateString());

    $sales = PurchaseItem::with(['product.category'])
        ->where('shop_id', auth()->user()->shop_id) // 👈 LIMIT TO MANAGER'S SHOP
        ->when($search, function ($query, $search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        })
        ->whereDate('created_at', $date)
        ->orderBy('created_at', 'desc')
        ->get();
            
        $shops = Shop::all();

        return view('manager.manage-sales', compact('sales', 'search', 'date', 'shops'));
}


public function showReceipt(Request $request, $id)
{
    $item = PurchaseItem::with('shop')->findOrFail($id); // load shop relation
    $transactionId = $item->transaction_id;

    $items = PurchaseItem::with('product')
        ->where('transaction_id', $transactionId)
        ->get();

    $total = $items->sum('total_price');

    $cashier = auth()->check() ? auth()->user()->name : 'Unknown Cashier';
    $shopName = $item->shop ? $item->shop->name : 'Unknown Shop';

    if ($request->wantsJson()) {
        return response()->json([
            'success'        => true,
            'transaction_id' => $transactionId,
            'items'          => $items,
            'total'          => $total,
            'cashier'        => $cashier,
            'shop'           => $shopName,
        ]);
    }

    return view('receipts.receipt', compact('items', 'total', 'cashier', 'shopName'));
}




public function destroy($id)
{
    // Find the sale record
    $sale = PurchaseItem::with('product')->findOrFail($id);

    // ✅ Restore product stock
    if ($sale->product) {
        $sale->product->increment('stock_quantity', $sale->quantity);
    }

    // ✅ Delete the sale
    $sale->delete();

    return response()->json([
        'success' => true,
        'message' => 'Sale deleted and product stock restored successfully.',
    ]);
}


}
