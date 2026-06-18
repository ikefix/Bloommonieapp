<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\Shop;
use App\Models\Notification;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\Auth;
use App\Models\PurchaseItem; // make sure this is at the top
use Illuminate\Support\Str;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{

public function import(Request $request)
{
    $request->validate([
        'excel_file' => 'required|file|mimes:xlsx,csv'
    ]);

    Excel::import(new ProductsImport, $request->file('excel_file'));

    return back()->with('success', 'Products imported successfully 🚀');
}
    

//     public function store(Request $request)
// {
//     $user = Auth::user();

//     // Authorization logic
//     if ($user->role === 'manager') {
//         $hasPermission = \App\Models\ProductPermission::where('manager_id', $user->id)->exists();

//         if (!$hasPermission) {
//             abort(403, 'You are not allowed to add products.');
//         }
//     } elseif ($user->role !== 'admin') {
//         abort(403, 'Unauthorized action.');
//     }

//     if (!auth()->user()->canCreateMoreProducts()) {

//         return back()->with(
//             'error',
//             'Product limit reached for your plan.'
//         );
//     }

//     // ✅ Validation
//     $request->validate([
//         'category_id' => 'required|exists:categories,id',
//         'shop_id' => 'required|exists:shops,id',
//         'name' => 'required|string|max:255',
//         'barcode' => 'required|string|unique:products,barcode', // ✅ must exist & unique
//         'price' => 'required|numeric|min:0',
//         'cost_price' => 'required|numeric|min:0',
//         'stock_limit' => 'required|numeric|min:0',
//         'stock_quantity' => 'required|integer|min:0',
//     ]);

//     // Check if product already exists in same shop & category
//     $existingProduct = Product::where('name', $request->name)
//         ->where('shop_id', $request->shop_id)
//         ->where('category_id', $request->category_id)
//         ->first();

//     if ($existingProduct) {
//         session()->flash('error', 'Product already exists in this shop and category.');
//         return redirect()->back()->withInput();
//     }

//     // ✅ Safe creation (now includes barcode)
//     $data = $request->only([
//         'category_id',
//         'shop_id',
//         'name',
//         'barcode', // ✅ Added
//         'price',
//         'cost_price',
//         'stock_limit',
//         'stock_quantity',
//     ]);

//     $product = Product::create($data);

//     // Stock check
//     $this->checkStockNotification($product);

//     session()->flash('success', 'Product stocked successfully!');
//     return redirect()->route('products.create');
// }

public function store(Request $request)
{
    $user = Auth::user();

    // Authorization logic
    if ($user->role === 'manager') {

        $hasPermission = \App\Models\ProductPermission::where(
            'manager_id',
            $user->id
        )->exists();

        if (!$hasPermission) {
            abort(403, 'You are not allowed to add products.');
        }

    } elseif ($user->role !== 'admin') {

        abort(403, 'Unauthorized action.');
    }

    if (!auth()->user()->canCreateMoreProducts()) {

        return back()->with(
            'error',
            'Product limit reached for your plan.'
        );
    }

    // ✅ Validation
    $request->validate([
        'category_id'    => 'required|exists:categories,id',
        'shop_id'        => 'required|exists:shops,id',
        'name'           => 'required|string|max:255',
        'barcode'        => 'required|string|unique:products,barcode',
        'price'          => 'required|numeric|min:0',
        'cost_price'     => 'required|numeric|min:0',
        'stock_limit'    => 'required|numeric|min:0',

        // changed from integer to numeric
        'stock_quantity' => 'required|numeric|min:0',

        // Manufacturing fields (optional)
        'stock_unit'     => 'nullable|string|max:50',
        'unit_size'      => 'nullable|numeric|min:0',
    ]);

    // Check if product already exists in same shop & category
    $existingProduct = Product::where('name', $request->name)
        ->where('shop_id', $request->shop_id)
        ->where('category_id', $request->category_id)
        ->first();

    if ($existingProduct) {

        session()->flash(
            'error',
            'Product already exists in this shop and category.'
        );

        return redirect()
            ->back()
            ->withInput();
    }

    $data = $request->only([
        'category_id',
        'shop_id',
        'name',
        'barcode',
        'price',
        'cost_price',
        'stock_limit',
        'stock_quantity',

        // Manufacturing fields
        'stock_unit',
        'unit_size',
    ]);

    $product = Product::create($data);

    $this->checkStockNotification($product);

    session()->flash(
        'success',
        'Product stocked successfully!'
    );

    return redirect()->route('products.create');
}


// 



    // Show details of a specific product
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }

    // Update an existing product (admin only)
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'shop_id' => 'required|exists:shops,id', // 💥 Add this line
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        // Prevent stock quantity from going below zero
        if ($request->stock_quantity < 0) {
            return response()->json(['error' => 'Stock quantity cannot be negative'], 422);
        }

        $product->update($request->all());

        // Notify admin if stock quantity is at or below reorder level after update
        $this->checkStockNotification($product);

        return response()->json(['message' => 'Product updated successfully', 'product' => $product]);
    }

    // Delete a product (admin only)
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }



public function searchSuggestions(Request $request)
{
    try {
        $query = strtolower(trim($request->input('query')));
        $shopId = auth()->check() ? auth()->user()->shop_id : null;

        if (!$query) {
            return response()->json([]); // empty search
        }

        $products = Product::where('shop_id', $shopId)
            ->where(function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
                  ->orWhere('barcode', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'price', 'barcode']);

        // ✅ If the query exactly matches a barcode, return one product
        $exactBarcode = Product::where('shop_id', $shopId)
            ->where('barcode', $query)
            ->first(['id', 'name', 'price', 'barcode']);

        if ($exactBarcode) {
            return response()->json([
                'success' => true,
                'name' => $exactBarcode->name,
                'price' => $exactBarcode->price,
                'id' => $exactBarcode->id,
                'barcode' => $exactBarcode->barcode,
            ]);
        }

        // ✅ Otherwise, return list for name search suggestion
        return response()->json($products);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
}





    // ✅ Private function to check and send low stock notifications
    private function checkStockNotification(Product $product)
    {
        if ($product->stock_quantity <= $product->reorder_level) {
            $admin = User::whereIn('role', ['admin', 'manager'])->first();

            if ($admin) {
                $admin->notify(new LowStockAlert($product));
            }
        }
    }

    // Create a product (admin only)
    public function create(Request $request)
{
    if (Auth::user()->role !== 'admin') {
        abort(403, 'Unauthorized action.');
    }

    $search = $request->input('search');
    $categories = Category::all();
    $shops = Shop::all(); // 🛒 get all shops

    $products = Product::with('category')
        ->when($search, function ($query, $search) {
            $query->where('name', 'like', "%$search%");
        })
        ->paginate(10);

    if ($request->ajax()) {
        return view('products.partials.table', compact('products'))->render();
    }

    return view('products.create', compact('categories', 'products', 'shops'));
}


    

    // Fetch products by category (for AJAX suggestions)
    public function getByCategory($categoryId)
    {
        $products = Product::where('category_id', $categoryId)->get(['name']);
        return response()->json($products);
    }




    // Update stock quantity and notify admin if stock is low
    private function updateStockAndNotify(Product $product, $quantitySold)
    {
        // Check if stock is below the stock limit
        if ($product->stock_quantity <= $product->stock_limit && $product->stock_limit > 0) {
            // Send notification to admin if stock is below the limit
            $admin = User::where('role', ['admin', 'manager'])->first();
            if ($admin) {
                $admin->notify(new LowStockAlert($product)); // Send LowStockAlert notification
            }
        }
    
        // Optional: If stock is zero, delete the product (you can change this logic if needed)
        if ($product->stock_quantity <= 0) {
            $product->delete();
        }
    }
    


    // Example use case when a sale happens in your controller:
    // Sale processing method
public function sellProduct(Request $request, $productId)
{
    // ✅ Validate input
    $request->validate([
        'quantity' => 'required|integer|min:1',
        'payment_method' => 'required|string', // e.g., cash, transfer, pos
    ]);

    $product = Product::findOrFail($productId);
    $quantitySold = $request->input('quantity');

    // ✅ Stock check
    if ($product->stock_quantity < $quantitySold) {
        return redirect()->back()->with('error', 'Not enough stock available.');
    }

    // ✅ Reduce stock
    $product->stock_quantity -= $quantitySold;
    $product->save();

    // ✅ Unique transaction ID
    $transactionId = 'TXN-' . now()->format('YmdHis') . '-' . rand(1000, 9999);

    // ✅ Save in purchase_items
    $purchase = PurchaseItem::create([
        'product_id'     => $product->id,
        'category_id'    => $product->category_id,
        'quantity'       => $quantitySold,
        'total_price'    => $product->price * $quantitySold,
        'payment_method' => $request->payment_method,
        'transaction_id' => $transactionId,
        'shop_id'        => auth()->user()->shop_id,
    ]);

    // ✅ Check stock notification
    $this->updateStockAndNotify($product, $quantitySold);

    // ✅ Redirect to receipt
    return redirect()->route('receipt.show', $purchase->id);
}

public function liveSearch(Request $request)
{
    // Use either 'search' or 'query' depending on your JS
    $search = $request->input('search') ?? $request->input('query');

    $query = Product::with(['category', 'shop']);

    if ($search) {
        $query->where('name', 'like', "%{$search}%");
    }

    $products = $query->paginate(10)->appends(['search' => $search]);

    if ($request->ajax()) {
        return view('products.partials.table', compact('products'))->render();
    }

    $categories = Category::all();
    $shops = Shop::all();
    return view('products.create', compact('products', 'categories', 'shops', 'search'));
}



public function getStock($id)
{
    $product = Product::find($id);

    if (!$product) {
        return response()->json(['error' => 'Product not found'], 404);
    }

    return response()->json([
        'stock' => $product->stock_quantity
    ]);
}

}