<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\Shop;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function index()
    {
        $productions = Production::with([
                'productionType',
                'shop'
            ])
            

            ->latest()
            ->paginate(20);

        
        $shops = Shop::all();

        return view('admin.production.index', compact('productions','shops'));
    }

   public function create()
{
    $productionTypes = ProductionType::where('status', 1)->get();
    $shops = Shop::all();

    return view('admin.production.create', compact(
        'productionTypes',
        'shops'
    ));
}

    public function store(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'production_type_id' => 'required|exists:production_types,id',
            'title' => 'required|string|max:255',
        ]);

        $last = Production::latest('id')->first();

        $next = $last ? $last->id + 1 : 1;

        $batchNo = 'BATCH-' . str_pad($next, 3, '0', STR_PAD_LEFT);

        Production::create([
            'shop_id' => $request->shop_id,
            'batch_no' => $batchNo,
            'production_type_id' => $request->production_type_id,
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status ?? 'planned',
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.production.index')
            ->with('success', 'Production Batch Created');
    }

    public function updateStatus(Request $request, Production $production)
{
    $request->validate([
        'status' => 'required|in:pending,in_progress,completed,cancelled'
    ]);

    $production->update([
        'status' => $request->status
    ]);

    return back()->with('success', 'Status updated successfully.');
}
}