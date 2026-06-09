<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionType;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function index()
    {
        $productions = Production::with('productionType')
            ->latest()
            ->paginate(20);

        return view('admin.production.index', compact('productions'));
    }

    public function create()
    {
        $productionTypes = ProductionType::where('status',1)->get();

        return view('admin.production.create', compact('productionTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'production_type_id' => 'required',
            'title' => 'required'
        ]);

        $last = Production::latest('id')->first();

        $next = $last ? $last->id + 1 : 1;

        $batchNo = 'BATCH-' . str_pad($next, 3, '0', STR_PAD_LEFT);

        Production::create([
            'batch_no' => $batchNo,
            'production_type_id' => $request->production_type_id,
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'created_by' => auth()->id()
        ]);

        return redirect()
            ->route('admin.production.index')
            ->with('success','Production Batch Created');
    }
}