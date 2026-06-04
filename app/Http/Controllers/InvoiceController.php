<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\Shop;
use App\Models\User;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;

use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function create()
    {
        $customers = Customer::all();
        $shops = Shop::all();

        // FIX: Fetch ALL products, not just user's shop
        $products = Product::all();

        if (Auth::user()->role === 'admin') {
            return view('admin.invoices.create', compact('customers', 'products', 'shops'));
        } elseif (Auth::user()->role === 'manager') {
            return view('manager.invoices.create', compact('customers', 'products', 'shops'));
        } else {
            return view('cashier.invoices.create', compact('customers', 'products', 'shops'));
        }
    }

public function store(Request $request)
{
    // Decode goods JSON first
    $request->merge([
        'goods' => json_decode($request->goods, true)
    ]);

    $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'shop_id'     => 'required|exists:shops,id',
        'goods'       => 'required|array|min:1',
        'goods.*.product_id' => 'required|exists:products,id',
        'goods.*.quantity'   => 'required|numeric|min:1',
        'total'       => 'required|numeric',
        'payment_type'=> 'required|in:full,part',
        'balance'     => 'nullable|numeric',
    ]);

    $goods = $request->goods;

    // Check stock for all products
    foreach ($goods as $item) {
        $product = Product::findOrFail($item['product_id']);
        if ($product->stock_quantity < $item['quantity']) {
            return back()->with('error', "Not enough stock for {$product->name}");
        }
    }

    // Create invoice
    $status = ($request->balance > 0) ? 'owing' : 'paid';

    $invoice = Invoice::create([
        'customer_id'    => $request->customer_id,
        'user_id'        => Auth::id(),
        'shop_id'        => $request->shop_id,
        'invoice_number' => 'INV-' . time(),
        'invoice_date'   => now(),
        'goods'          => $goods, // Laravel casts array to JSON automatically
        'total'          => $request->total,
        'discount'       => $request->discount ?? 0,
        'tax'            => $request->tax ?? 0,
        'payment_type'   => $request->payment_type,
        'amount_paid'    => $request->amount_paid ?? $request->total,
        'balance'        => $request->balance ?? 0,
        'payment_status' => $status,
    ]);

    // Create PurchaseItems + decrement stock
    foreach ($goods as $item) {
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
            'owner_id'       => auth()->user()->owner_id,
        ]);
    }

    return back()->with('success', 'Invoice + Sale recorded');
}


public function destroy(Invoice $invoice)
{
    if ($invoice->balance > 0) {
        return back()->with('error', 'Cannot delete invoice with unpaid balance.');
    }

    $invoice->delete();

    return back()->with('success', 'Invoice deleted successfully.');
}



public function preview(Invoice $invoice)
{
    return view('admin.invoices.preview', compact('invoice'));
}

public function download(Invoice $invoice)
{
    $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'));

    return $pdf->download('Invoice-'.$invoice->invoice_number.'.pdf');
}


public function generateShareLink(Invoice $invoice)
{
    $link = URL::temporarySignedRoute(
        'invoice.share',
        now()->addDays(3),
        ['invoice' => $invoice->id]
    );

    return $link;
}



/*
|--------------------------------------------------------------------------
| SEARCH CUSTOMER INVOICES
|--------------------------------------------------------------------------
*/
public function searchCustomerInvoices(Request $request)
{
    $search = $request->search;

    $invoices = Invoice::with(['customer', 'shop'])

        ->when($search, function ($query) use ($search) {

            $query->whereHas('customer', function ($q) use ($search) {

                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");

            });

        })

        ->latest()

        ->get();

    $owingInvoices = $invoices->where('payment_status', 'owing');

    $totalInvoices = $invoices->count();

    if (Auth::user()->role === 'admin') {

        return view(
            'admin.invoices.owing',
            compact('invoices', 'owingInvoices', 'totalInvoices')
        );

    } elseif (Auth::user()->role === 'manager') {

        return view(
            'manager.invoices.owing',
            compact('invoices', 'owingInvoices', 'totalInvoices')
        );

    } else {

        return view(
            'cashier.invoices.owing',
            compact('invoices', 'owingInvoices', 'totalInvoices')
        );

    }
}


/*
|--------------------------------------------------------------------------
| DELETE INVOICE
|--------------------------------------------------------------------------
*/

public function deleteInvoice(Invoice $invoice)
{
    // CHECK IF STILL OWING
    if ($invoice->balance > 0) {

        return back()->with(
            'error',
            'Please pay the remaining balance before deleting this invoice.'
        );
    }

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | RETURN STOCK BACK
        |--------------------------------------------------------------------------
        */

        foreach ($invoice->goods as $item) {

            $product = Product::find($item['product_id']);

            if ($product) {

                $product->increment(
                    'stock_quantity',
                    $item['quantity']
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE PURCHASE ITEMS
        |--------------------------------------------------------------------------
        */

        PurchaseItem::where('invoice_id', $invoice->id)->delete();

        /*
        |--------------------------------------------------------------------------
        | DELETE INVOICE
        |--------------------------------------------------------------------------
        */

        $invoice->delete();

        DB::commit();

        return back()->with(
            'success',
            'Invoice deleted successfully.'
        );

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->with(
            'error',
            'Something went wrong: ' . $e->getMessage()
        );
    }
}



public function owing()
{
    if (!in_array(Auth::user()->role, ['admin', 'manager', 'cashier'])) {
        abort(403);
    }

    // Dashboard stats: ONLY owing invoices
    $owingInvoices = Invoice::with('customer', 'shop')
        ->where('payment_status', 'owing')
        ->orderBy('invoice_date', 'desc')
        ->get();

    // Table data: ALL invoices (paid + owing)
    $invoices = Invoice::with('customer', 'shop')
        ->orderBy('invoice_date', 'desc')
        ->get();

    // Total invoice count
    $totalInvoices = Invoice::count();

    if (Auth::user()->role === 'admin') {
        return view('admin.invoices.owing', compact('owingInvoices', 'invoices', 'totalInvoices'));
    } elseif (Auth::user()->role === 'manager') {
        return view('manager.invoices.owing', compact('owingInvoices', 'invoices', 'totalInvoices'));
    } else {
        return view('cashier.invoices.owing', compact('owingInvoices', 'invoices', 'totalInvoices'));
    }

}




// InvoiceController.php

// Show edit payment page
public function editPayment(Invoice $invoice)
{
    if (!in_array(Auth::user()->role, ['admin', 'manager'])) {
        abort(403);
    }

    return view(
        Auth::user()->role === 'admin' ? 'admin.invoices.edit-payment' : 'manager.invoices.edit-payment', 
        compact('invoice')
    );
}

public function updatePayment(Request $request, Invoice $invoice)
{
    $request->validate([
        'amount_paid' => 'required|numeric|min:1',
        'payment_type' => 'required',
    ]);

    // Existing values
    $currentPaid = $invoice->amount_paid;
    $currentBalance = $invoice->balance;

    // New payment entered
    $newPayment = $request->amount_paid;

    // Prevent overpayment
    if ($newPayment > $currentBalance) {
        return back()->with('error', 'Payment exceeds remaining balance');
    }

    // Calculate new totals
    $totalPaid = $currentPaid + $newPayment;
    $newBalance = $invoice->total - $totalPaid;

    // Determine payment status
    $paymentStatus = $newBalance <= 0 ? 'paid' : 'owing';

    // Update invoice
    $invoice->update([
        'amount_paid' => $totalPaid,
        'balance' => $newBalance,
        'payment_status' => $paymentStatus,
        'payment_type' => $request->payment_type,
    ]);

    return back()->with('success', 'Payment updated successfully');
}

public function markPaid(Invoice $invoice)
{
    // Already paid
    if ($invoice->payment_status === 'paid') {
        return back()->with('error', 'Invoice already paid');
    }

    // Add remaining balance to amount paid
    $invoice->update([

        'amount_paid' => $invoice->amount_paid + $invoice->balance,

        'balance' => 0,

        'payment_status' => 'paid',

        'payment_type' => 'full',
    ]);

    return back()->with('success', 'Invoice marked as paid');
}


public function editPaymentcash(Invoice $invoice)
{
    if (Auth::user()->role !== 'cashier') {
        abort(403);
    }

    return view('cashier.invoices.edit-payment', compact('invoice'));
}




public function updatePaymentcash(Request $request, Invoice $invoice)
{
    $request->validate([
        'amount_paid' => 'required|numeric|min:1',
        'payment_type' => 'required',
    ]);

    // Existing values
    $currentPaid = $invoice->amount_paid;
    $currentBalance = $invoice->balance;

    // New payment entered
    $newPayment = $request->amount_paid;

    // Prevent overpayment
    if ($newPayment > $currentBalance) {
        return back()->with('error', 'Payment exceeds remaining balance');
    }

    // Calculate new totals
    $totalPaid = $currentPaid + $newPayment;
    $newBalance = $invoice->total - $totalPaid;

    // Determine payment status
    $paymentStatus = $newBalance <= 0 ? 'paid' : 'owing';

    // Update invoice
    $invoice->update([
        'amount_paid' => $totalPaid,
        'balance' => $newBalance,
        'payment_status' => $paymentStatus,
        'payment_type' => $request->payment_type,
    ]);

    return back()->with('success', 'Payment updated successfully');
}

public function receivables(Request $request)
{
    $ownerId = auth()->user()->owner_id;

    /*
    |--------------------------------------------------------------------------
    | STEP 1: GET CUSTOMER IDS (WITH SEARCH)
    |--------------------------------------------------------------------------
    */

    $customerIdsQuery = Customer::query()
        ->whereHas('invoices', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        });

    if ($request->search) {
        $search = $request->search;

        $customerIdsQuery->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    $customerIds = $customerIdsQuery->pluck('id');

    /*
    |--------------------------------------------------------------------------
    | STEP 2: AGGREGATE INVOICES
    |--------------------------------------------------------------------------
    */

    $customers = Invoice::select(
            'customer_id',
            DB::raw('SUM(total) as total_invoice'),
            DB::raw('SUM(amount_paid) as total_paid'),
            DB::raw('SUM(balance) as total_owing'),
            DB::raw('MAX(shop_id) as shop_id')
        )
        ->with(['customer', 'shop'])
        ->where('owner_id', $ownerId)
        ->whereIn('customer_id', $customerIds) // 🔥 KEY FIX
        ->groupBy('customer_id')
        ->paginate(10)
        ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | STEP 3: GOODS (ALL INVOICES PER CUSTOMER)
    |--------------------------------------------------------------------------
    */

    $invoicesByCustomer = Invoice::where('owner_id', $ownerId)
        ->with('shop')
        ->get()
        ->groupBy('customer_id');

    /*
    |--------------------------------------------------------------------------
    | STEP 4: TOTAL RECEIVABLE
    |--------------------------------------------------------------------------
    */

    $totalReceivable = Invoice::where('owner_id', $ownerId)->sum('balance');

    return view('admin.invoices.receivables', compact(
        'customers',
        'invoicesByCustomer',
        'totalReceivable'
    ));
}


public function receivablesforcash(Request $request)
{
    $ownerId = auth()->user()->owner_id;

    /*
    |--------------------------------------------------------------------------
    | STEP 1: GET CUSTOMER IDS (WITH SEARCH)
    |--------------------------------------------------------------------------
    */

    $customerIdsQuery = Customer::query()
        ->whereHas('invoices', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        });

    if ($request->search) {
        $search = $request->search;

        $customerIdsQuery->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    $customerIds = $customerIdsQuery->pluck('id');

    /*
    |--------------------------------------------------------------------------
    | STEP 2: AGGREGATE INVOICES
    |--------------------------------------------------------------------------
    */

    $customers = Invoice::select(
            'customer_id',
            DB::raw('SUM(total) as total_invoice'),
            DB::raw('SUM(amount_paid) as total_paid'),
            DB::raw('SUM(balance) as total_owing'),
            DB::raw('MAX(shop_id) as shop_id')
        )
        ->with(['customer', 'shop'])
        ->where('owner_id', $ownerId)
        ->whereIn('customer_id', $customerIds) // 🔥 KEY FIX
        ->groupBy('customer_id')
        ->paginate(10)
        ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | STEP 3: GOODS (ALL INVOICES PER CUSTOMER)
    |--------------------------------------------------------------------------
    */

    $invoicesByCustomer = Invoice::where('owner_id', $ownerId)
        ->with('shop')
        ->get()
        ->groupBy('customer_id');

    /*
    |--------------------------------------------------------------------------
    | STEP 4: TOTAL RECEIVABLE
    |--------------------------------------------------------------------------
    */

    $totalReceivable = Invoice::where('owner_id', $ownerId)->sum('balance');

    return view('cashier.invoices.receivables', compact(
        'customers',
        'invoicesByCustomer',
        'totalReceivable'
    ));
}

}

