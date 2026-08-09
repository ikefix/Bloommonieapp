<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePaymentLog;
use Carbon\Carbon;
use App\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use Illuminate\Http\Request;

class CollectableController extends Controller
{
    // GET /api/admin/collectables?start_date=&end_date=&shop_id=&cashier_id=
    public function index(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::today()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::today()->endOfDay();

        $shopId = $request->shop_id;
        $cashierId = $request->cashier_id;

        // Shops dropdown
        $shops = Shop::where('owner_id', auth()->user()->owner_id)
            ->orderBy('name')
            ->get();

        // Cashiers dropdown
        $cashiers = User::where('owner_id', auth()->user()->owner_id)
            ->where('role', 'cashier')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | INVOICES QUERY
        |--------------------------------------------------------------------------
        */

        $invoiceQuery = Invoice::with('cashier')
            ->whereBetween('invoice_date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ])
            ->whereNotNull('user_id');

        if ($shopId && $shopId != 'all') {
            $invoiceQuery->where('shop_id', $shopId);
        }

        if ($cashierId && $cashierId != 'all') {
            $invoiceQuery->where('user_id', $cashierId);
        }

        $invoices = $invoiceQuery
            ->get()
            ->groupBy('user_id');

        /*
        |--------------------------------------------------------------------------
        | PAYMENT LOGS QUERY
        |--------------------------------------------------------------------------
        */

        $paymentLogsQuery = InvoicePaymentLog::whereBetween(
            'payment_updated_at',
            [$startDate, $endDate]
        );

        if ($cashierId && $cashierId != 'all') {
            $paymentLogsQuery->where('cashier_id', $cashierId);
        }

        if ($shopId && $shopId != 'all') {

            $shopCashierIds = User::where('shop_id', $shopId)
                ->where('role', 'cashier')
                ->pluck('id');

            $paymentLogsQuery->whereIn(
                'cashier_id',
                $shopCashierIds
            );
        }

        $paymentLogs = $paymentLogsQuery
            ->get()
            ->groupBy('cashier_id');

        return response()->json([
            'status' => true,
            'data'   => [
                'invoices'     => $invoices,
                'payment_logs' => $paymentLogs,
                'shops'        => $shops,
                'cashiers'     => $cashiers,
                'shop_id'      => $shopId,
                'cashier_id'   => $cashierId,
                'start_date'   => $startDate->toDateString(),
                'end_date'     => $endDate->toDateString(),
            ],
        ]);
    }

    // GET /api/admin/collectables/download?start_date=&end_date=&shop_id=&cashier_id=
    // Returns a binary PDF response — same file content-type as the web version.
    public function downloadPdf(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::today()->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::today()->endOfDay();

        $shopId = $request->shop_id;
        $cashierId = $request->cashier_id;

        $invoiceQuery = Invoice::with('cashier')
            ->whereBetween('invoice_date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ]);

        if ($shopId && $shopId != 'all') {
            $invoiceQuery->where('shop_id', $shopId);
        }

        if ($cashierId && $cashierId != 'all') {
            $invoiceQuery->where('user_id', $cashierId);
        }

        $invoices = $invoiceQuery
            ->get()
            ->groupBy('user_id');

        $paymentLogsQuery = InvoicePaymentLog::whereBetween(
            'payment_updated_at',
            [$startDate, $endDate]
        );

        if ($cashierId && $cashierId != 'all') {
            $paymentLogsQuery->where('cashier_id', $cashierId);
        }

        $paymentLogs = $paymentLogsQuery
            ->get()
            ->groupBy('cashier_id');

        $pdf = Pdf::loadView(
            'admin.collectables_pdf',
            compact(
                'invoices',
                'paymentLogs',
                'startDate',
                'endDate'
            )
        );

        return $pdf->download(
            'collectables_' .
            now()->format('Ymd_His') .
            '.pdf'
        );
    }
}