<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    // GET ALL STAFF WITH THEIR SHOPS
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $ownerId = $request->user()->owner_id ?? $request->user()->id;

        $users = User::with('shop')
            ->whereIn('role', ['manager', 'cashier'])
            ->where('owner_id', $ownerId)
            ->get();

        $shops = Shop::where('user_id', $request->user()->id)->get();

        return response()->json([
            'status' => true,
            'users' => $users,
            'shops' => $shops,
        ]);
    }

    // UPDATE ROLE
    public function updateRole(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $request->validate([
            'role' => 'required|in:cashier,manager,admin',
        ]);

        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User role updated successfully.',
            'user' => $user,
        ]);
    }

    // DELETE USER
    public function deleteUser(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $user = User::findOrFail($id);

        if ($user->id == $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    // UPDATE SHOP ASSIGNMENT
    public function updateShop(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $request->validate([
            'shop_id' => 'required|exists:shops,id',
        ]);

        $user = User::findOrFail($id);
        $user->shop_id = $request->shop_id;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Shop assigned successfully.',
            'user' => $user,
        ]);
    }
}