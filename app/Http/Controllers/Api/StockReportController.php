<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class StockReportController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | OWNER
        |--------------------------------------------------------------------------
        */

        $ownerId = auth()->user()->owner_id ?? auth()->id();


        /*
        |--------------------------------------------------------------------------
        | OWNER SHOPS ONLY
        |--------------------------------------------------------------------------
        */

        $shops = Shop::where('owner_id', $ownerId)
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        $tableQuery = Product::with('shop')
            ->where('owner_id', $ownerId);


        /*
        |--------------------------------------------------------------------------
        | SHOP FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('shop_id')) {

            $tableQuery->where(
                'shop_id',
                $request->shop_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $tableQuery->where(
                'name',
                'like',
                '%' . $request->search . '%'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        $products = $tableQuery
            ->select(
                'products.id',
                'products.name',
                'products.shop_id',
                'products.stock_quantity',
                'products.stock_limit',
                'products.stock_unit',
                'products.unit_size',

                DB::raw(
                    'products.stock_quantity as opening_stock'
                ),

                DB::raw(
                    '0 as stock_added'
                ),

                DB::raw(
                    '0 as stock_sold'
                ),

                DB::raw(
                    'products.stock_quantity as remaining_stock'
                )
            )
            ->orderBy('products.name')
            ->paginate(20)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | STATS — OWNER ONLY
        |--------------------------------------------------------------------------
        */

        $statsQuery = Product::where(
            'owner_id',
            $ownerId
        );

        $totalProducts = $statsQuery->count();

        $lowStockCount = (clone $statsQuery)
            ->whereColumn(
                'stock_quantity',
                '<=',
                'stock_limit'
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | STOCK CHART
        |--------------------------------------------------------------------------
        */

        $stockChart = [
            'labels' => [
                'Low Stock',
                'Normal Stock'
            ],

            'data' => [
                $lowStockCount,
                max(
                    $totalProducts - $lowStockCount,
                    0
                )
            ]
        ];


        /*
        |--------------------------------------------------------------------------
        | API RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => true,

            'data' => [
                'shops' => $shops,

                'products' => $products,

                'total_products' => $totalProducts,

                'low_stock_count' => $lowStockCount,

                'stock_chart' => $stockChart,
            ]
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    public function downloadPdf(Request $request)
    {
        $ownerId = auth()->user()->owner_id ?? auth()->id();


        /*
        |--------------------------------------------------------------------------
        | OWNER PRODUCTS ONLY
        |--------------------------------------------------------------------------
        */

        $query = Product::with('shop')
            ->where('owner_id', $ownerId);


        /*
        |--------------------------------------------------------------------------
        | SHOP FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('shop_id')) {

            $query->where(
                'shop_id',
                $request->shop_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $query->where(
                'name',
                'like',
                '%' . $request->search . '%'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        $products = $query
            ->select(
                'products.id',
                'products.name',
                'products.shop_id',
                'products.stock_quantity',
                'products.stock_limit',
                'products.stock_unit',
                'products.unit_size',

                DB::raw(
                    'products.stock_quantity as opening_stock'
                ),

                DB::raw(
                    '0 as stock_added'
                ),

                DB::raw(
                    '0 as stock_sold'
                ),

                DB::raw(
                    'products.stock_quantity as remaining_stock'
                )
            )
            ->orderBy('products.name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
            'admin.report.stock_pdf',
            compact('products')
        )->setPaper(
            'a4',
            'landscape'
        );

        return $pdf->download(
            'stock_inventory_report.pdf'
        );
    }
}