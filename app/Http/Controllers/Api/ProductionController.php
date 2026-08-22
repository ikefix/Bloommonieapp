<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    // GET /api/admin/production?search=&shop_id=&page=
    public function index(Request $request)
    {
        $user = $request->user();

        // Enforce the plan lock server-side — same pattern as the report
        // controllers. This was missing entirely in the original conversion;
        // without it, any account could hit this route directly regardless
        // of plan.
        if (!$user->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => $user->getLimitMessage('feature', 'Production Management'),
                'data' => null,
            ]);
        }

        $ownerId = $user->owner_id ?: $user->id;

        $query = Production::with(['productionType', 'shop'])
            ->where('owner_id', $ownerId);

        // 🔍 Search by batch number or title
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('batch_no', 'like', '%' . $request->search . '%')
                  ->orWhere('title', 'like', '%' . $request->search . '%');
            });
        }

        // 🏬 Filter by shop
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        $productions = $query->latest()->paginate(20)->withQueryString();

        $shops = Shop::where('owner_id', $ownerId)->get();

        return response()->json([
            'status' => true,
            'locked' => false,
            'data' => [
                'productions' => $productions, // paginator: {data, current_page, last_page, ...}
                'shops'       => $shops,
            ],
        ]);
    }

    // GET /api/admin/production/create
    // Returns the productionTypes + shops needed to build the create form
    public function create(Request $request)
    {
        $user = $request->user();

        if (!$user->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => $user->getLimitMessage('feature', 'Production Management'),
                'data' => null,
            ]);
        }

        $ownerId = $user->owner_id ?: $user->id;

        $productionTypes = ProductionType::where('owner_id', $ownerId)->get();
        $shops = Shop::where('owner_id', $ownerId)->get();

        return response()->json([
            'status' => true,
            'locked' => false,
            'data' => [
                'production_types' => $productionTypes,
                'shops'             => $shops,
            ],
        ]);
    }

    // POST /api/admin/production
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => $user->getLimitMessage('feature', 'Production Management'),
                'data' => null,
            ]);
        }

        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'production_type_id' => 'required|exists:production_types,id',
            'title' => 'required|string|max:255',
        ]);

        $ownerId = $user->owner_id ?: $user->id;

        // Get last batch for THIS owner only
        $lastProduction = Production::where('owner_id', $ownerId)
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastProduction) {
            $nextNumber = (int) preg_replace(
                '/[^0-9]/',
                '',
                $lastProduction->batch_no
            ) + 1;
        }

        $batchNo = 'BATCH-' . str_pad(
            $nextNumber,
            3,
            '0',
            STR_PAD_LEFT
        );

        $production = Production::create([
            'shop_id'            => $request->shop_id,
            'batch_no'           => $batchNo,
            'production_type_id' => $request->production_type_id,
            'title'              => $request->title,
            'description'        => $request->description,
            'start_date'         => $request->start_date,
            'end_date'           => $request->end_date,
            'status'             => $request->status ?? 'planned',
            'created_by'         => $user->id,
            'owner_id'           => $ownerId,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Production Batch Created Successfully',
            'data'    => $production->load(['productionType', 'shop']),
        ], 201);
    }

    // PATCH /api/admin/production/{production}/status
    public function updateStatus(Request $request, Production $production)
    {
        $request->validate([
            'status' => 'required|in:planned,in_progress,completed,cancelled'
        ]);

        $production->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Status updated successfully.',
            'data'    => $production->fresh(['productionType', 'shop']),
        ]);
    }
}