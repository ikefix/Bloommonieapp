<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;

class UnitController extends Controller
{
public function index()
{
    $units = Unit::where('owner_id', auth()->id())->get();
    return view('admin.units.index', compact('units'));
}

public function store(Request $request)
{
    $request->validate([
        'name' => 'required',
        'symbol' => 'nullable'
    ]);

    Unit::create([
        'owner_id' => auth()->id(),
        'name' => $request->name,
        'symbol' => $request->symbol,
    ]);

    return back()->with('success', 'Unit created successfully');
}

public function destroy($id)
{
    Unit::findOrFail($id)->delete();
    return back()->with('success', 'Unit deleted');
}
}
