<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionType;
use Illuminate\Http\Request;
use App\Models\User;

class ProductionTypeController extends Controller
{
    // GET /api/admin/production-types?page=
    public function index()
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

        $ownerId = $user->owner_id ?: $user->id;

        $productionTypes = ProductionType::whereHas('creator', function ($query) use ($ownerId) {

            $query->where('id', $ownerId)
                  ->orWhere('owner_id', $ownerId);

        })
        ->latest()
        ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => [
                'production_types' => $productionTypes,
            ],
        ]);
    }

    // NOTE: web version's create() just returned a blank form view with no
    // data — nothing for an API to hand back, so it's omitted here. store()
    // below needs nothing prefetched (name/status/description are typed
    // directly), so there's no equivalent "create" call needed on mobile.

    // POST /api/admin/production-types
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
            'name' => 'required|unique:production_types,name',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string|max:1000',
        ]);

        $productionType = ProductionType::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
            'created_by' => auth()->id(),
            'owner_id' => auth()->user()->owner_id ?: auth()->id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Production Type created successfully.',
            'data'    => $productionType,
        ], 201);
    }

    // DELETE /api/admin/production-types/{id}
    public function destroy($id)
    {
        if (!auth()->user()->hasFeature('production_management')) {
            return response()->json([
                'status' => true,
                'locked' => true,
                'message' => auth()->user()->getLimitMessage('feature', 'Production Management'),
            ]);
        }

        ProductionType::findOrFail($id)->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Production Type deleted.',
        ]);
    }
}