<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Production;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProductionEntryController extends Controller
{

public function index()
{
    $ownerId = auth()->user()->owner_id ?: auth()->id();

    $productions = Production::where('owner_id', $ownerId)
        ->latest()
        ->paginate(20);

    return view('admin.production_entries.index', compact('productions'));
}


public function show(Production $production)
{
    $production->load('entries');

    return view('admin.production_entries.show', compact('production'));
}

// public function fill(Production $production)
// {
//     $production->load('entries');

//     return view('admin.production_entries.fill', compact('production'));
// }

public function fill(Production $production)
{
    $production->load('entries');

    $products = Product::where('shop_id', $production->shop_id)
        ->orderBy('name')
        ->get();

    return view(
        'admin.production_entries.fill',
        compact('production', 'products')
    );
}

public function edit(Production $production)
{
    $production->load('entries');

        $products = Product::where('shop_id', $production->shop_id)
        ->orderBy('name')
        ->get();

    return view(
        'admin.production_entries.edit',
        compact('production', 'products')
    );
}


// public function store(Request $request, $productionId)
// {
//     $request->validate([
//         'entry_type' => 'required',
//         'items'      => 'required|array',
//     ]);

//     DB::transaction(function () use ($request, $productionId) {

//         // Stock movements first
//         foreach ($request->items as $item) {
//             if (empty($item['item_id'])) continue;

//             $qty     = (float) ($item['quantity'] ?? 0);
//             $product = Product::findOrFail($item['item_id']);

//             if (in_array($request->entry_type, ['input', 'loss'])) {
//                 if ($product->stock_quantity < $qty) {
//                     throw new \Exception("Not enough stock for {$product->name}");
//                 }
//                 $product->decrement('stock_quantity', $qty);
//             }

//             if ($request->entry_type === 'output') {
//                 $product->increment('stock_quantity', $qty);
//             }
//         }

//         // Find existing record for this production + type
//         $entry = ProductionEntry::where('production_id', $productionId)
//                      ->where('entry_type', $request->entry_type)
//                      ->first();

//         if ($entry) {
//             // APPEND new items into the existing meta array
//             $existingItems = $entry->meta['items'] ?? [];
//             $entry->meta   = ['items' => array_merge($existingItems, $request->items)];
//             $entry->save();
//         } else {
//             // First time — create the record
//             ProductionEntry::create([
//                 'production_id' => $productionId,
//                 'entry_type'    => $request->entry_type,
//                 'meta'          => ['items' => $request->items],
//                 'owner_id' => auth()->user()->owner_id ?: auth()->id(),
//             ]);
//         }
//     });

//     return back()->with('success', 'Saved successfully');
// }


public function store(Request $request, $productionId)
{
    $request->validate([
        'entry_type' => 'required',
        'items'      => 'required|array',
    ]);

    DB::transaction(function () use ($request, $productionId) {

        // Stamp each item with the current datetime
        $timestampedItems = array_map(function ($item) {
            $item['added_at'] = now()->toDateTimeString(); // e.g. "2025-06-17 14:32:00"
            return $item;
        }, $request->items);

        // Stock movements first
        foreach ($timestampedItems as $item) {
            if (empty($item['item_id'])) continue;

            $qty     = (float) ($item['quantity'] ?? 0);
            $product = Product::findOrFail($item['item_id']);

            if (in_array($request->entry_type, ['input', 'loss'])) {
                if ($product->stock_quantity < $qty) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }
                $product->decrement('stock_quantity', $qty);
            }

            if ($request->entry_type === 'output') {
                $product->increment('stock_quantity', $qty);
            }
        }

        // Find existing record for this production + type
        $entry = ProductionEntry::where('production_id', $productionId)
                     ->where('entry_type', $request->entry_type)
                     ->first();

        if ($entry) {
            // APPEND new timestamped items into the existing meta array
            $existingItems = $entry->meta['items'] ?? [];
            $entry->meta   = ['items' => array_merge($existingItems, $timestampedItems)];
            $entry->save();
        } else {
            // First time — create the record
            ProductionEntry::create([
                'production_id' => $productionId,
                'entry_type'    => $request->entry_type,
                'meta'          => ['items' => $timestampedItems],
                'owner_id'      => auth()->user()->owner_id ?: auth()->id(),
            ]);
        }
    });

    return back()->with('success', 'Saved successfully');
}



public function update(Request $request, $productionId)
{
    $request->validate([
        'entry_type' => 'required|in:input,output,loss',
        'items'      => 'required|array',
    ]);

    DB::transaction(function () use ($request, $productionId) {

        // Find the ONE record for this production + type
        $existing = ProductionEntry::where('production_id', $productionId)
                        ->where('entry_type', $request->entry_type)
                        ->first();

        // ── Reverse old stock ──────────────────────────────────────────────
        if ($existing) {
            $oldMeta  = is_string($existing->meta)
                            ? json_decode($existing->meta, true)
                            : $existing->meta;

            $oldItems = $oldMeta['items'] ?? [];

            foreach ($oldItems as $oldItem) {
                if (empty($oldItem['item_id'])) continue;

                $product = Product::find($oldItem['item_id']);
                if (!$product) continue;

                $oldQty = (float) ($oldItem['quantity'] ?? 0);

                if (in_array($existing->entry_type, ['input', 'loss'])) {
                    $product->increment('stock_quantity', $oldQty);
                }
                if ($existing->entry_type === 'output') {
                    $product->decrement('stock_quantity', $oldQty);
                }
            }
        }

        // ── Apply new stock ────────────────────────────────────────────────
        $newItems = array_values($request->items ?? []);

        foreach ($newItems as $item) {
            if (empty($item['item_id']) || empty($item['quantity'])) continue;

            $product = Product::findOrFail($item['item_id']);
            $qty     = (float) $item['quantity'];

            if (in_array($request->entry_type, ['input', 'loss'])) {
                if ((float) $product->stock_quantity < $qty) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }
                $product->decrement('stock_quantity', $qty);
            }

            if ($request->entry_type === 'output') {
                $product->increment('stock_quantity', $qty);
            }
        }

        // ── Save ───────────────────────────────────────────────────────────
        ProductionEntry::updateOrCreate(
            [
                'production_id' => $productionId,
                'entry_type'    => $request->entry_type,
            ],
            [
                'meta' => ['items' => $newItems]
            ]
        );
    });

    return back()->with('success', 'Entry updated successfully');
}

    public function destroy($id)
    {
        ProductionEntry::findOrFail($id)->delete();

        return back()->with('success', 'Entry deleted');
    }

}
