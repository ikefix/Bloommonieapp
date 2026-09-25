<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    public function getProduct(Request $request, $barcode)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $ownerId = $user->owner_id ?? $user->id;

        $product = Product::where('barcode', $barcode)
            ->where('owner_id', $ownerId)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found for this barcode.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'stock_quantity' => $product->stock_quantity,
                'stock_limit' => $product->stock_limit,
                'barcode' => $product->barcode,
                'category_id' => $product->category_id,
                'shop_id' => $product->shop_id,
            ],
        ]);
    }
}