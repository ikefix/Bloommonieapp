<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;

class ShopController extends Controller
{

    // GET ALL SHOPS FOR LOGGED IN USER
    public function index(Request $request)
    {
        $shops = $request->user()->shops ?? collect();

        return response()->json([
            'status' => true,
            'shops' => $shops,
        ]);
    }

    // CREATE SHOP
    public function store(Request $request)
    {
        if (!$request->user()->canCreateMoreStores()) {
            return response()->json([
                'status' => false,
                'message' => 'Your current plan has reached the maximum store limit.',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $shop = Shop::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'location' => $request->location,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Shop created successfully',
            'shop' => $shop,
        ]);
    }

    // SHOW SINGLE SHOP
    public function show(Request $request, $id)
    {
        $shop = Shop::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$shop) {
            return response()->json([
                'status' => false,
                'message' => 'Shop not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'shop' => $shop,
        ]);
    }

    // UPDATE SHOP
    public function update(Request $request, $id)
    {
        $shop = Shop::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$shop) {
            return response()->json([
                'status' => false,
                'message' => 'Shop not found',
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $shop->update($request->only('name', 'location'));

        return response()->json([
            'status' => true,
            'message' => 'Shop updated successfully',
            'shop' => $shop,
        ]);
    }

    // DELETE SHOP
    public function destroy(Request $request, $id)
    {
        $shop = Shop::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$shop) {
            return response()->json([
                'status' => false,
                'message' => 'Shop not found',
            ], 404);
        }

        $shop->delete();

        return response()->json([
            'status' => true,
            'message' => 'Shop deleted successfully',
        ]);
    }
}