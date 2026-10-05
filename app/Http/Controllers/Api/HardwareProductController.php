<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HardwareProduct;
use Illuminate\Http\Request;

class HardwareProductController extends Controller
{
    // Get all products
    public function index()
    {
        $products = HardwareProduct::latest()->get();

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    // Add a product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string',
        ]);

        $product = HardwareProduct::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully',
            'product' => $product
        ], 201);
    }

    // Get one product
    public function show($id)
    {
        $product = HardwareProduct::findOrFail($id);

        return response()->json([
            'success' => true,
            'product' => $product
        ]);
    }
}