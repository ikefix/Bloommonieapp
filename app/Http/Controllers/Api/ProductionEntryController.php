<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Production;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Shop;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class ProductionEntryController extends Controller
{
    // GET /api/admin/production-entries?page=
    public function index()
    {
        if (!auth()->user()->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => auth()->user()->getLimitMessage('feature', 'Production Management'),
                'data' => null,
            ]);
        }

        $ownerId = auth()->user()->owner_id ?: auth()->id();

        $productions = Production::where('owner_id', $ownerId)
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => [
                'productions' => $productions,
            ],
        ]);
    }

    // GET /api/admin/production-entries/{production}
    public function show(Production $production)
    {
        if (!auth()->user()->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => auth()->user()->getLimitMessage('feature', 'Production Management'),
                'data' => null,
            ]);
        }

        $production->load('entries');

        return response()->json([
            'status' => true,
            'data' => $production,
        ]);
    }

    // GET /api/admin/production-entries/{production}/fill
    // Returns everything needed to build the "fill entry" form:
    // the production, its shop's products, and this owner's units.
    public function fill(Production $production)
    {
        if (!auth()->user()->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => auth()->user()->getLimitMessage('feature', 'Production Management'),
                'data' => null,
            ]);
        }

        $production->load('entries');

        $products = Product::where('shop_id', $production->shop_id)
            ->orderBy('name')
            ->get();

        $units = Unit::where('owner_id', auth()->id())
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'production' => $production,
                'products'   => $products,
                'units'      => $units,
            ],
        ]);
    }

    // GET /api/admin/production-entries/{production}/edit
    // Same shape as fill() — kept separate to mirror the original web routes.
    public function edit(Production $production)
    {
        if (!auth()->user()->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => auth()->user()->getLimitMessage('feature', 'Production Management'),
                'data' => null,
            ]);
        }

        $production->load('entries');

        $products = Product::where('shop_id', $production->shop_id)
            ->orderBy('name')
            ->get();

        $units = Unit::where('owner_id', auth()->id())
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'production' => $production,
                'products'   => $products,
                'units'      => $units,
            ],
        ]);
    }

    // POST /api/admin/production-entries/{productionId}
    // entry_type: input | output | loss
    // items: [{ item_id, quantity, price, unit, ... }, ...]
    public function store(Request $request, $productionId)
    {
        if (!auth()->user()->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => auth()->user()->getLimitMessage('feature', 'Production Management'),
                'data' => null,
            ]);
        }

        $request->validate([
            'entry_type' => 'required',
            'items'      => 'required|array',
        ]);

        DB::transaction(function () use ($request, $productionId) {

            // Stamp each item with the current datetime
            $timestampedItems = array_map(function ($item) {
                $item['added_at'] = now()->toDateTimeString();
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

        return response()->json([
            'status'  => true,
            'message' => 'Saved successfully',
            'data'    => ProductionEntry::where('production_id', $productionId)
                ->where('entry_type', $request->entry_type)
                ->first(),
        ]);
    }

    // PUT /api/admin/production-entries/{productionId}
    public function update(Request $request, $productionId)
    {
        if (!auth()->user()->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => auth()->user()->getLimitMessage('feature', 'Production Management'),
                'data' => null,
            ]);
        }

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

        return response()->json([
            'status'  => true,
            'message' => 'Entry updated successfully',
            'data'    => ProductionEntry::where('production_id', $productionId)
                ->where('entry_type', $request->entry_type)
                ->first(),
        ]);
    }

    // DELETE /api/admin/production-entries/{id}
    public function destroy($id)
    {
        if (!auth()->user()->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => auth()->user()->getLimitMessage('feature', 'Production Management'),
                'data' => null,
            ]);
        }

        ProductionEntry::findOrFail($id)->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Entry deleted',
        ]);
    }
}