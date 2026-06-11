<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductionType;
use Illuminate\Http\Request;

class ProductionTypeController extends Controller
{
public function index()
{
    $user = auth()->user();

    if ($user->role === 'admin') {

        $productionTypes = ProductionType::latest()->paginate(20);

    } else {

        $productionTypes = ProductionType::where('created_by', $user->id)
            ->latest()
            ->paginate(20);
    }

    return view('admin.production_type.index', compact('productionTypes'));
}

    public function create()
    {
        return view('admin.production_type.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:production_types,name',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string|max:1000',
        ]);

        ProductionType::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.production_type.index')
            ->with('success', 'Production Type created successfully.');
    }

    public function destroy($id)
    {
        ProductionType::findOrFail($id)->delete();

        return back()->with('success', 'Production Type deleted.');
    }
}