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
        return response()->json([
            'success' => true,
            'products' => HardwareProduct::latest()->get()
        ]);
    }

    // Add a product
    public function store(Request $request)
    {
        $this->checkAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'required|string|max:255',
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
        return response()->json([
            'success' => true,
            'product' => HardwareProduct::findOrFail($id)
        ]);
    }

    // Edit a product
    public function update(Request $request, $id)
    {
        $this->checkAdmin($request);

        $product = HardwareProduct::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'category' => 'sometimes|required|string|max:255',
            'image' => 'nullable|string',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $product->fresh()
        ]);
    }

    // Delete a product
    public function destroy(Request $request, $id)
    {
        $this->checkAdmin($request);

        HardwareProduct::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    // Same admin login used by the orders page
    private function checkAdmin(Request $request): void
    {
        $password = (string) config('admin.password');

        abort_unless(
            $password !== '' &&
            $request->header('X-Admin-User') === config('admin.username') &&
            hash_equals($password, (string) $request->header('X-Admin-Key')),
            401
        );
    }
}