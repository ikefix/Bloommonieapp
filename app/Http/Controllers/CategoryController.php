<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\Product;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::all());
    }

    public function create()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $categories = Category::all();
        $products = Product::all();

        return view('categories.create', compact('categories', 'products'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        // $request->validate([
        //     'name' => 'required|unique:categories,name|max:100',
        //     'product_id' => 'nullable|exists:products,id',
        // ]);

        $request->validate([
            'name' => [
                'required',
                'max:100',
                \Illuminate\Validation\Rule::unique('categories')
                    ->where(function ($query) {
                        return $query->where('owner_id', auth()->id());
                    }),
            ],
            
            // 'product_id' => 'nullable|exists:products,id',
        ]);

        $category = Category::create([
            'name' => $request->name,
        ]);

        if ($request->product_id) {
            $product = Product::find($request->product_id);
            $product->category_id = $category->id;
            $product->save();
        }

        return redirect()->route('categories.create')->with('success', 'Category created successfully!');
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:categories,name,' . $id . '|max:100',
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Category updated successfully!');

    }

    public function destroy($id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        Category::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Category deleted successfully!');
    }
}
