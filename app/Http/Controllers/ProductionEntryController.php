<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Production;
use App\Models\ProductionEntry;

class ProductionEntryController extends Controller
{

public function index()
{
    $productions = Production::latest()->get();

    return view('admin.production_entries.index', compact('productions'));
}


public function show(Production $production)
{
    $production->load('entries');

    return view('admin.production_entries.show', compact('production'));
}

public function fill(Production $production)
{
    $production->load('entries');

    return view('admin.production_entries.fill', compact('production'));
}

public function edit(Production $production)
{
    $production->load('entries');

    return view(
        'admin.production_entries.edit',
        compact('production')
    );
}

    public function store(Request $request, $productionId)
{
    $request->validate([
        'entry_type' => 'required',
        'items' => 'required|array',
    ]);

    ProductionEntry::updateOrCreate(
        [
            'production_id' => $productionId,
            'entry_type' => $request->entry_type,
        ],
        [
            'meta' => [
                'items' => $request->items
            ]
        ]
    );

    return back()->with('success', 'Batch entry saved successfully');
}

    public function update(Request $request, $id)
    {
        $entry = ProductionEntry::findOrFail($id);

        $entry->update([
            'item_name' => $request->item_name,
            'quantity' => $request->quantity,
            'unit' => $request->unit,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Entry updated successfully');
    }

    public function destroy($id)
    {
        ProductionEntry::findOrFail($id)->delete();

        return back()->with('success', 'Entry deleted');
    }

}
