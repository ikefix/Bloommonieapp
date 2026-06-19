<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{

    // GET ALL CATEGORIES FOR LOGGED IN OWNER
    public function index(Request $request)
    {
        $categories = Category::where('owner_id', $request->user()->owner_id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'categories' => $categories,
        ]);
    }

    // CREATE CATEGORY
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $request->validate([
            'name' => [
                'required',
                'max:100',
                Rule::unique('categories')->where(function ($query) use ($request) {
                    return $query->where('owner_id', $request->user()->owner_id);
                }),
            ],
            'product_id' => 'nullable|exists:products,id',
        ]);

        $category = Category::create([
            'owner_id' => $request->user()->owner_id,
            'name' => $request->name,
        ]);

        if ($request->product_id) {
            $product = Product::find($request->product_id);
            $product->category_id = $category->id;
            $product->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Category created successfully',
            'category' => $category,
        ]);
    }

    // SHOW SINGLE CATEGORY
    public function show(Request $request, $id)
    {
        $category = Category::where('id', $id)
            ->where('owner_id', $request->user()->owner_id)
            ->first();

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'category' => $category,
        ]);
    }

    // UPDATE CATEGORY
    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $category = Category::where('id', $id)
            ->where('owner_id', $request->user()->owner_id)
            ->first();

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $request->validate([
            'name' => [
                'required',
                'max:100',
                Rule::unique('categories')
                    ->ignore($category->id)
                    ->where(function ($query) use ($request) {
                        return $query->where('owner_id', $request->user()->owner_id);
                    }),
            ],
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category updated successfully',
            'category' => $category,
        ]);
    }

    // DELETE CATEGORY
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $category = Category::where('id', $id)
            ->where('owner_id', $request->user()->owner_id)
            ->first();

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Category deleted successfully',
        ]);
    }
}