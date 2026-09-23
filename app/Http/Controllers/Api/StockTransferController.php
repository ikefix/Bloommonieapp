<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockTransfer;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class StockTransferController extends Controller
{
    /**
     * Create a stock transfer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'shop_id' => 'required|exists:shops,id',
            'to_shop_id' => 'required|exists:shops,id|different:shop_id',
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        try {
            $result = DB::transaction(function () use ($validated) {

                /*
                |--------------------------------------------------------------------------
                | Get source product
                |--------------------------------------------------------------------------
                */

                $product = Product::where('id', $validated['product_id'])
                    ->where('shop_id', $validated['shop_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw ValidationException::withMessages([
                        'product_id' => [
                            'The selected product does not belong to the source shop.'
                        ]
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Check available stock
                |--------------------------------------------------------------------------
                */

                if ($product->stock_quantity < $validated['quantity']) {
                    throw ValidationException::withMessages([
                        'quantity' => [
                            'Not enough stock in the source shop.'
                        ]
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Create stock transfer record
                |--------------------------------------------------------------------------
                */

                $stockTransfer = StockTransfer::create([
                    'product_id' => $validated['product_id'],
                    'shop_id' => $validated['shop_id'],
                    'to_shop_id' => $validated['to_shop_id'],
                    'quantity' => $validated['quantity'],
                    'cost_price' => $validated['cost_price'],
                    'selling_price' => $validated['selling_price'],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Deduct stock from source shop
                |--------------------------------------------------------------------------
                */

                $product->stock_quantity -= $validated['quantity'];
                $product->save();

                /*
                |--------------------------------------------------------------------------
                | Check low stock
                |--------------------------------------------------------------------------
                */

                if ($product->stock_quantity <= $product->stock_limit) {

                    $admins = User::whereIn('role', [
                        'admin',
                        'manager'
                    ])->get();

                    if ($admins->count() > 0) {
                        Notification::send(
                            $admins,
                            new LowStockAlert($product)
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Find product in destination shop
                |--------------------------------------------------------------------------
                */

                $destProduct = Product::where('id', $validated['product_id'])
                    ->where('shop_id', $validated['to_shop_id'])
                    ->lockForUpdate()
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Product already exists in destination shop
                |--------------------------------------------------------------------------
                */

                if ($destProduct) {

                    $destProduct->stock_quantity += $validated['quantity'];
                    $destProduct->save();

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Product ID may be different in destination shop.
                    | Check using name + category.
                    |--------------------------------------------------------------------------
                    */

                    $existing = Product::where('name', $product->name)
                        ->where('category_id', $product->category_id)
                        ->where('shop_id', $validated['to_shop_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($existing) {

                        $existing->stock_quantity += $validated['quantity'];
                        $existing->save();

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Create product in destination shop
                        |--------------------------------------------------------------------------
                        */

                        $destProduct = Product::create([
                            'name' => $product->name,
                            'category_id' => $product->category_id,
                            'cost_price' => $product->cost_price,
                            'price' => $product->price,
                            'shop_id' => $validated['to_shop_id'],
                            'stock_quantity' => $validated['quantity'],
                            'stock_limit' => $product->stock_limit,
                        ]);
                    }
                }

                return $stockTransfer;
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock transfer completed successfully.',
                'data' => $result,
            ], 201);

        } catch (ValidationException $e) {

            throw $e;

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to complete stock transfer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get shops.
     */
    public function shops()
    {
        $shops = Shop::all();

        return response()->json([
            'success' => true,
            'data' => $shops,
        ]);
    }


    /**
     * Get products.
     */
    public function products()
    {
        $products = Product::all();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }


    /**
     * Get categories.
     */
    public function categories()
    {
        $categories = Category::all();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }


    /**
     * Get products belonging to a specific shop.
     */
    public function getProductsByShop($shopId)
    {
        $shop = Shop::find($shopId);

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found.',
            ], 404);
        }

        $products = Product::where('shop_id', $shopId)
            ->get([
                'id',
                'name',
                'category_id',
                'cost_price',
                'price',
                'stock_quantity',
                'stock_limit',
            ]);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}