<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Models\PurchaseItem;
use App\Models\InvoicePaymentLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Notification;

class InvoiceController extends Controller
{
    // GET DATA NEEDED TO CREATE AN INVOICE (customers, shops, products)
    public function create()
    {
        $customers = Customer::all();
        $shops = Shop::all();
        $products = Product::all();

        return response()->json([
            'status' => true,
            'data' => compact('customers', 'shops', 'products'),
        ]);
    }

    // CREATE INVOICE
    public function store(Request $request)
    {
        $request->validate([
            'customer_id'        => 'required|exists:customers,id',
            'shop_id'            => 'required|exists:shops,id',
            'goods'              => 'required|array|min:1',
            'goods.*.product_id' => 'required|exists:products,id',
            'goods.*.quantity'   => 'required|numeric|min:1',
            'goods.*.total_price'=> 'required|numeric|min:0',
            'total'              => 'required|numeric',
            'payment_type'       => 'required|in:full,part',
            'amount_paid'        => 'nullable|numeric',
            'balance'            => 'nullable|numeric',
            'discount'           => 'nullable|numeric',
            'tax'                => 'nullable|numeric',
        ]);

        // STOCK CHECK
        foreach ($request->goods as $item) {
            $product = Product::findOrFail($item['product_id']);

            if ($product->stock_quantity < $item['quantity']) {
                return response()->json([
                    'status'  => false,
                    'message' => "Not enough stock for {$product->name}",
                ], 400);
            }
        }

        $status = ($request->balance > 0) ? 'owing' : 'paid';

        DB::beginTransaction();

        try {
            $invoice = Invoice::create([
                'customer_id'    => $request->customer_id,
                'user_id'        => Auth::id(),
                'shop_id'        => $request->shop_id,
                'invoice_number' => 'INV-' . time(),
                'invoice_date'   => now(),
                'goods'          => $request->goods,
                'total'          => $request->total,
                'discount'       => $request->discount ?? 0,
                'tax'            => $request->tax ?? 0,
                'payment_type'   => $request->payment_type,
                'amount_paid'    => $request->amount_paid ?? $request->total,
                'balance'        => $request->balance ?? 0,
                'payment_status' => $status,
            ]);

            foreach ($request->goods as $item) {
                $product = Product::findOrFail($item['product_id']);
                $product->decrement('stock_quantity', $item['quantity']);

                PurchaseItem::create([
                    'transaction_id' => $invoice->invoice_number,
                    'invoice_id'     => $invoice->id,
                    'product_id'     => $product->id,
                    'category_id'    => $product->category_id,
                    'shop_id'        => $request->shop_id,
                    'quantity'       => $item['quantity'],
                    'total_price'    => $item['total_price'],
                    'discount'       => $request->discount ?? 0,
                    'cashier_id'     => Auth::id(),
                    'sale_type'      => 'invoice',
                    'owner_id'       => Auth::user()->owner_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Invoice created successfully',
                'data'    => $invoice->load('customer', 'shop'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // PREVIEW INVOICE
    public function preview($id)
    {
        $invoice = Invoice::with('customer', 'shop')->find($id);

        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $invoice,
        ]);
    }

    // DOWNLOAD INVOICE AS PDF
    public function download($id)
    {
        $invoice = Invoice::with('customer', 'shop')->find($id);

        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'));

        return $pdf->download('Invoice-' . $invoice->invoice_number . '.pdf');
    }

    // GET ALL INVOICES (with owing stats)
    public function owing()
    {
        $invoices = Invoice::with('customer', 'shop')
            ->orderBy('invoice_date', 'desc')
            ->get();

        $owingInvoices  = $invoices->where('payment_status', 'owing')->values();
        $totalInvoices  = $invoices->count();
        $totalOwing     = $owingInvoices->sum('balance');

        return response()->json([
            'status' => true,
            'data'   => [
                'all_invoices'   => $invoices,
                'owing_invoices' => $owingInvoices,
                'total_invoices' => $totalInvoices,
                'total_owing'    => $totalOwing,
            ],
        ]);
    }

    // GET SINGLE INVOICE
    public function show($id)
    {
        $invoice = Invoice::with('customer', 'shop')->find($id);

        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $invoice,
        ]);
    }

    // DELETE INVOICE (returns stock)
    public function destroy($id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        if ($invoice->balance > 0) {
            return response()->json([
                'status'  => false,
                'message' => 'Cannot delete invoice with unpaid balance.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // RETURN STOCK
            foreach ($invoice->goods as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->increment('stock_quantity', $item['quantity']);
                }
            }

            PurchaseItem::where('invoice_id', $invoice->id)->delete();
            $invoice->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Invoice deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // SEARCH INVOICES BY CUSTOMER NAME OR PHONE
    public function search(Request $request)
    {
        $search = $request->input('query');

        $invoices = Invoice::with('customer', 'shop')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        $owingInvoices = $invoices->where('payment_status', 'owing')->values();
        $totalInvoices = $invoices->count();

        return response()->json([
            'status' => true,
            'data'   => [
                'invoices'       => $invoices,
                'owing_invoices' => $owingInvoices,
                'total_invoices' => $totalInvoices,
            ],
        ]);
    }

    // UPDATE PAYMENT (admin/manager)
    public function updatePayment(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        $request->validate([
            'amount_paid'  => 'required|numeric|min:1',
            'payment_type' => 'required|string',
        ]);

        $newPayment = $request->amount_paid;

        if ($newPayment > $invoice->balance) {
            return response()->json([
                'status'  => false,
                'message' => 'Payment exceeds remaining balance',
            ], 400);
        }

        $totalPaid     = $invoice->amount_paid + $newPayment;
        $newBalance    = $invoice->total - $totalPaid;
        $paymentStatus = $newBalance <= 0 ? 'paid' : 'owing';

        $invoice->update([
            'amount_paid'    => $totalPaid,
            'balance'        => $newBalance,
            'payment_status' => $paymentStatus,
            'payment_type'   => $request->payment_type,
        ]);

        InvoicePaymentLog::create([
            'owner_id'           => Auth::user()->owner_id,
            'invoice_id'         => $invoice->id,
            'invoice_no'         => $invoice->invoice_number,
            'cashier_id'         => Auth::id(),
            'type'               => 'invoice_payment',
            'message'            => Auth::user()->name . ' updated Invoice #' . $invoice->invoice_number,
            'amount_added'       => $newPayment,
            'total_paid'         => $totalPaid,
            'balance'            => $newBalance,
            'updated_by'         => Auth::user()->name,
            'updated_by_id'      => Auth::id(),
            'payment_updated_at' => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Payment updated successfully',
            'data'    => $invoice->fresh(),
        ]);
    }

    // MARK INVOICE AS FULLY PAID
    public function markPaid($id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice not found',
            ], 404);
        }

        if ($invoice->payment_status === 'paid') {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice is already paid',
            ], 400);
        }

        $invoice->update([
            'amount_paid'    => $invoice->amount_paid + $invoice->balance,
            'balance'        => 0,
            'payment_status' => 'paid',
            'payment_type'   => 'full',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Invoice marked as paid',
            'data'    => $invoice->fresh(),
        ]);
    }

    // RECEIVABLES (grouped by customer)
    public function receivables(Request $request)
    {
        $ownerId = Auth::user()->owner_id;
        $search  = $request->input('query');

        $customerIdsQuery = Customer::query()
            ->whereHas('invoices', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            });

        if ($search) {
            $customerIdsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customerIds = $customerIdsQuery->pluck('id');

        $customers = Invoice::select(
                'customer_id',
                DB::raw('SUM(total) as total_invoice'),
                DB::raw('SUM(amount_paid) as total_paid'),
                DB::raw('SUM(balance) as total_owing'),
                DB::raw('MAX(shop_id) as shop_id')
            )
            ->with(['customer', 'shop'])
            ->where('owner_id', $ownerId)
            ->whereIn('customer_id', $customerIds)
            ->groupBy('customer_id')
            ->get();

        $totalReceivable = Invoice::where('owner_id', $ownerId)->sum('balance');

        return response()->json([
            'status' => true,
            'data'   => [
                'customers'        => $customers,
                'total_receivable' => $totalReceivable,
            ],
        ]);
    }
}