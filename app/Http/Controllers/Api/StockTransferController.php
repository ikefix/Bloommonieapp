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
            $result = DB::transaction(function () use ($validated, $request) {

                $user = $request->user();

                /*
                |--------------------------------------------------------------------------
                | Determine owner
                |--------------------------------------------------------------------------
                */

                $ownerId = $user->owner_id ?? $user->id;

                /*
                |--------------------------------------------------------------------------
                | Verify source shop belongs to owner
                |--------------------------------------------------------------------------
                */

                $sourceShop = Shop::where('id', $validated['shop_id'])
                    ->where('owner_id', $ownerId)
                    ->first();

                if (!$sourceShop) {
                    throw ValidationException::withMessages([
                        'shop_id' => [
                            'The selected source shop does not belong to your account.'
                        ]
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Verify destination shop belongs to owner
                |--------------------------------------------------------------------------
                */

                $destinationShop = Shop::where('id', $validated['to_shop_id'])
                    ->where('owner_id', $ownerId)
                    ->first();

                if (!$destinationShop) {
                    throw ValidationException::withMessages([
                        'to_shop_id' => [
                            'The selected destination shop does not belong to your account.'
                        ]
                    ]);
                }

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

                    $admins = User::where('owner_id', $ownerId)
                        ->whereIn('role', [
                            'admin',
                            'manager',
                        ])
                        ->get();

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

                    /*
                    | Keep the transfer prices if supplied.
                    */
                    $destProduct->cost_price = $validated['cost_price'];
                    $destProduct->price = $validated['selling_price'];

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

                        $existing->cost_price = $validated['cost_price'];
                        $existing->price = $validated['selling_price'];

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
                            'cost_price' => $validated['cost_price'],
                            'price' => $validated['selling_price'],
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
     * Get shops belonging to the logged-in owner.
     */
    public function shops(Request $request)
    {
        $user = $request->user();

        $ownerId = $user->owner_id ?? $user->id;

        $shops = Shop::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $shops,
        ]);
    }


    /**
     * Get products.
     */
    public function products(Request $request)
    {
        $user = $request->user();

        $ownerId = $user->owner_id ?? $user->id;

        $products = Product::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }


    /**
     * Get categories.
     */
    public function categories(Request $request)
    {
        $user = $request->user();

        $ownerId = $user->owner_id ?? $user->id;

        $categories = Category::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }


    /**
     * Get products belonging to a specific shop.
     *
     * Used by the Flutter Stock Transfer product selector.
     *
     * The Flutter app can then search the returned list locally
     * by product name or barcode.
     */
    public function getProductsByShop(
        Request $request,
        $shopId
    ) {
        $user = $request->user();

        $ownerId = $user->owner_id ?? $user->id;

        /*
        |--------------------------------------------------------------------------
        | Verify that the shop belongs to the logged-in owner
        |--------------------------------------------------------------------------
        */

        $shop = Shop::where('id', $shopId)
            ->where('owner_id', $ownerId)
            ->first();

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found or you do not have access to this shop.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Get products for this shop
        |--------------------------------------------------------------------------
        |
        | Barcode is included because the Flutter product selector
        | searches by both product name and barcode.
        |
        */

        $products = Product::where('shop_id', $shopId)
            ->where('owner_id', $ownerId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'barcode',
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