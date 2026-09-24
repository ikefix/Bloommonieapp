<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlanAccessController extends Controller
{
    /**
     * Get the authenticated user's plan access.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => true,

            'plan' => [
                'name' => $user->plan->name ?? null,
            ],

            'features' => [
                'stock_transfer' => $user->hasFeature('stock_transfer'),
                'multi_shop' => $user->hasFeature('multi_shop'),
            ],

            'limits' => [
                'stores' => $this->getStoresLimit($user),
            ],
        ]);
    }

    /**
     * Get the user's store limit.
     *
     * This uses the existing canCreateMoreStores()
     * method as the authority for whether another
     * shop can be created.
     */
    private function getStoresLimit($user)
    {
        /*
         * If your application already has a direct
         * method/property for the store limit, use it here.
         *
         * For now, return null because the actual
         * permission is already enforced by:
         *
         * $user->canCreateMoreStores()
         */
        return null;
    }
}

