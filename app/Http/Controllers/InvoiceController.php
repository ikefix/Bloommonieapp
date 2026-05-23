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


//     public function store(Request $request)
// {
//     $request->validate([
//         'customer_id' => 'required|exists:customers,id',
//         'shop_id' => 'required|exists:shops,id',
//         'goods' => 'required|array',
//         'discount' => 'nullable|numeric',
//         'tax' => 'nullable|numeric',
//         'total' => 'required|numeric',
//         'payment_type' => 'required|in:full,part',
//         'amount_paid' => 'nullable|numeric',
//         'balance' => 'nullable|numeric',
//     ]);

//     // Extract product details
//     $productId = $request->goods['product_id'];
//     $qty = $request->goods['quantity'];

//     // Fetch product
//     $product = Product::findOrFail($productId);

//     // CHECK IF ENOUGH STOCK EXISTS
//     if ($product->stock_quantity < $qty) {
//         return back()->with('error', 'Not enough stock available. Current stock: ' . $product->stock_quantity);
//     }

//     // DEDUCT STOCK
//     $product->stock_quantity -= $qty;
//     $product->save();

//     // Determine payment status
//     $status = ($request->balance > 0) ? 'owing' : 'paid';

//     // Create invoice
//     Invoice::create([
//         'customer_id' => $request->customer_id,
//         'user_id' => Auth::id(),
//         'shop_id' => $request->shop_id,
//         'invoice_number' => 'INV-' . time(),
//         'invoice_date' => now(),
//         'goods' => $request->goods,
//         'discount' => $request->discount ?? 0,
//         'tax' => $request->tax ?? 0,
//         'total' => $request->total,
//         'payment_type' => $request->payment_type,
//         'amount_paid' => $request->amount_paid ?? 0,
//         'balance' => $request->balance ?? 0,
//         'payment_status' => $status,
//     ]);

//     return redirect()->back()->with('success', 'Invoice created successfully! Stock updated.');
// }

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
            'cashier_id'     => Auth::id(),
            'sale_type'      => 'invoice',
            'owner_id' => auth()->user()->owner_id,
        ]);
    }

    return back()->with('success', 'Invoice + Sale recorded');
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

public function updatePayment(Invoice $invoice)
{
    $invoice->update([
        'amount_paid' => $invoice->total,
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



public function updatePaymentcash(Invoice $invoice)
{
    $invoice->update([
        'amount_paid' => $invoice->total,
        'balance' => 0,
        'payment_status' => 'paid',
        'payment_type' => 'full',
    ]);

    return back()->with('success', 'Invoice marked as paid');
}
// Update payment
// public function updatePaymentcash(Request $request, Invoice $invoice)
// {
//     $request->validate([
//         'payment_type' => 'required|in:full,part',
//         'amount_paid' => 'required|numeric|min:0',
//     ]);

//     $totalAmount = $invoice->total;
//     $newAmountPaid = $invoice->amount_paid + $request->amount_paid;

//     // Calculate new balance
//     $balance = $totalAmount - $newAmountPaid;

//     $invoice->amount_paid = $newAmountPaid;
//     $invoice->balance = $balance;

//     // Update payment type and status
//     $invoice->payment_type = $request->payment_type;
//     $invoice->payment_status = $balance <= 0 ? 'paid' : 'owing';

//     $invoice->save();

//     return redirect()->route(Auth::user()->role . '.invoices.owing')
//                      ->with('success', 'Payment updated successfully!');
// }




}

