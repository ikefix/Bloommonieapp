<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProductPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductPermissionController extends Controller
{
    /**
     * Get managers, their permissions, and the authenticated
     * user's shops.
     */
    public function index()
    {
        $managers = User::where('role', 'manager')
            ->with('shop')
            ->get();

        $permissions = ProductPermission::pluck('manager_id')
            ->toArray();

        $shops = Auth::user()->shops ?? collect();

        return response()->json([
            'success' => true,
            'data' => [
                'managers' => $managers,
                'permissions' => $permissions,
                'shops' => $shops,
            ],
        ]);
    }


    /**
     * Grant product access to a manager.
     */
    public function grantAccess(Request $request)
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:users,id',
        ]);

        $manager = User::where('id', $validated['manager_id'])
            ->where('role', 'manager')
            ->first();

        if (!$manager) {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not a manager.',
            ], 422);
        }

        $permission = ProductPermission::firstOrCreate([
            'manager_id' => $validated['manager_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Access granted successfully.',
            'data' => $permission,
        ], 201);
    }


    /**
     * Revoke product access from a manager.
     */
    public function revokeAccess(Request $request)
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:users,id',
        ]);

        $permission = ProductPermission::where(
            'manager_id',
            $validated['manager_id']
        )->first();

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'No access record found for this manager.',
            ], 404);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Access revoked successfully.',
        ]);
    }
}