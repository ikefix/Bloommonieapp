<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseItemController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductPermissionController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\ProfitReportController;
use Milon\Barcode\DNS1D;
use App\Models\Product;
use App\Http\Controllers\ComplaintController;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Http\Request;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Auth\ProductKeyController;
use App\Http\Controllers\SuperAdminController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!

*/

Route::get('/home', function () {

    $user = Auth::user();

    if (!$user) {
        return redirect('/login');
    }

    // SUPERADMIN
    if ($user->role === 'superadmin') {
        return redirect('/superadmin-dashboard');
    }

    // OWNER CHECK
    $owner = $user->owner_id
        ? \App\Models\User::find($user->owner_id)
        : $user;

    // ACTIVATION CHECK
    if (!$owner->is_activated) {
        return redirect('/show-product-key');
    }

    // ROLE REDIRECT
    if ($user->role === 'admin') {
        return redirect('/admin-dashboard');
    }

    if ($user->role === 'manager') {
        return redirect('/manager-dashboard');
    }

    return redirect('/home');

})->middleware('auth');


Route::get('/pricing', function () {
    return view('pricing');
})->middleware('auth');


Route::middleware('auth')->group(function () {

Route::get('/subscribe/{plan}/{billing}', [SubscriptionController::class, 'pay'])
    ->name('subscribe.plan');

    Route::get('/payment/callback', [SubscriptionController::class, 'callback'])
        ->name('payment.callback');

});



// FOR SUPERADMIN


Route::middleware(['auth', 'superadmin'])->group(function () {
    Route::get('/superadmin-dashboard', [SuperAdminController::class, 'dashboard'])
        ->name('superadmin.dashboard');

    Route::get('/create-admin', [SuperAdminController::class, 'create'])
        ->name('superadmin.create');

    Route::post('/create-admin', [SuperAdminController::class, 'store'])
        ->name('superadmin.store');
});


Route::middleware(['auth'])->group(function () {

    // show product key page
    Route::get('/show-product-key', [ProductKeyController::class, 'show'])
        ->name('product.key.form');

    // check product key
    Route::post('/verify-product-key', [ProductKeyController::class, 'verify'])
        ->name('product.key.check');

});


Route::middleware(['auth', 'activated', 'subscription'])->group(function () {

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

        Route::get('/admin-dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard')->middleware('role:admin');

        Route::get('/manager-dashboard', [ManagerController::class, 'dashboard'])->name('manager.jop')->middleware('role:manager');

});

Route::get('/subscription-expired', function () {
    return view('subscription.expired');
});







Route::get('/products/export', function () {
    return Excel::download(new ProductsExport, 'products.xlsx');
})->name('products.export');


// Route::get('/barcode/{code}', function ($code) {
//     $barcode = new DNS1D();
//     // Generate a PNG barcode as base64
//     $png = $barcode->getBarcodePNG($code, 'C128');
//     // Return it as an inline image response (no file saved)
//     return response(base64_decode($png))
//         ->header('Content-Type', 'image/png');
// });

Route::get('/get-product/{barcode}', [App\Http\Controllers\BarcodeController::class, 'getProductName']);



Route::get('/barcode-Admin', function () {
    return view('barcode-manager');
})->name('barcode.manager');

Route::get('/get-product-name/{barcode}', [App\Http\Controllers\BarcodeController::class, 'getProductName']);


Route::get('/barcode-Manager', function () {
    return view('manager.barcode');
})->name('manager.barcode');


Route::get('/', function () {
    return view('welcome');
});

Auth::routes();


Route::get('/admin', [AdminController::class, 'index'])->middleware('role:admin');

Route::middleware(['auth'])->group(function () {
    // Route::get('/admin-dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard')->middleware('role:admin');
    // Route::get('/manager-dashboard', [ManagerController::class, 'index'])->name('manager.dashboard')->middleware('role:manager');
});

// FOR MANAGER

Route::get('/manager/sales', [PurchaseItemController::class, 'managersales'])->name('manage.sales');

// Route::get('/manager/notifications', [ManagerController::class, 'getNotifications'])->name('manager.notification');

Route::get('/manager/profile', [ManagerController::class, 'editProfile'])->name('manager.profile');

// Handle Profile Update
Route::post('/manager/profile/update', [ManagerController::class, 'updateProfile'])->name('manager.profile.update');

Route::get('/manager/register', [ManagerController::class, 'showRegisterForm'])->name('manager.register');

Route::get('/manager/manage-roles', [ManagerController::class, 'role'])->name('manager.manage_role');
Route::patch('/manager/update-role/{id}', [ManagerController::class, 'updateRole'])->name('manager.updateRole');



    // Route::get('/manager-dashboard', [ManagerController::class, 'dashboard'])->name('manager.jop')->middleware('role:manager');

Route::post('/manager/register', [ManagerController::class, 'storeStaff'])->name('manager.storeStaff');

// Route::get('/products/create', [ManagerController::class, 'create'])->name('manager.product');
// Route::post('/manager/product', [ManagerController::class, 'store'])->name('manager.store');
Route::post('/products', [ProductController::class, 'store'])->name('products.store');


    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');

// END OF MANAGER ROUTES







Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/manage-roles', [RoleController::class, 'index'])->name('admin.manage_roles');
    Route::patch('/admin/update-role/{id}', [RoleController::class, 'updateRole'])->name('admin.updateRole');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/register', [AdminController::class, 'showRegisterForm'])->name('admin.register');
});

Route::post('/admin/register', [AdminController::class, 'storeStaff'])->name('admin.storeStaff');


// Show Edit Profile Form
Route::get('/admin/profile', [AdminController::class, 'editProfile'])->name('admin.profile');

// Handle Profile Update
Route::post('/admin/profile/update', [AdminController::class, 'updateProfile'])->name('admin.profile.update');

// Route for admin sales page
// Route::get('/admin/sales', [AdminController::class, 'sales'])->name('admin.sales');
// Route::get('/admin/filter-sales', [AdminController::class, 'filterSales'])->name('admin.sales.filter');

Route::get('/admin/sales', [AdminController::class, 'salespage'])->name('admin.sales');
Route::get('/admin/filter-sales', [AdminController::class, 'filterSales'])->name('admin.sales.filter');
// Route::delete('/admin/sales/{id}', [AdminController::class, 'deleteSale'])->name('admin.sales.delete');

Route::delete('/admin/sales/{id}', [PurchaseItemController::class, 'destroy'])->name('admin.sales.destroy');



Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.deleteUser');
// Route::get('/admin/filter-sales', [PurchaseItemController::class, 'allSales']);


// PRODUCT CREATE
Route::middleware(['auth'])->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create')->middleware('admin');
    // Route::post('/products', [ProductController::class, 'store'])->name('products.store')->middleware('admin');
});


// CATEGORY CREATE
Route::middleware(['auth'])->group(function () {
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->name('categories.show');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});

Route::get('/products/by-category/{categoryId}', [App\Http\Controllers\ProductController::class, 'getByCategory']);
// Search
Route::get('/products/search-suggestions', [ProductController::class, 'searchSuggestions']);
// FOR ADMIN AND MANAGER
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('user.notifications');
});

Route::get('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.delete');

Route::get('/products/search', [ProductController::class, 'liveSearch'])->name('products.live-search');




//CASHIER CONTROLLER
Route::get('/purchaseitem/products/{categoryId}', [PurchaseItemController::class, 'getProductsByCategory']);

Route::post('/purchaseitem/store', [PurchaseItemController::class, 'store'])->name('purchaseitem.store');
Route::get('/purchaseitem/receipt/{id}', [PurchaseItemController::class, 'showReceipt'])->name('purchaseitem.receipt');


Route::get('/home', [PurchaseItemController::class, 'index'])->name('home');
Route::get('/cashiersales', [PurchaseItemController::class, 'cashiersales'])->name('cashier.home-sales');



Route::get('/api/product-stock/{id}', function ($id) {
    $product = \App\Models\Product::findOrFail($id);
    return response()->json(['stock' => $product->stock_quantity]);
});



// PERMISSION ACCESS

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/manager-permissions', [ProductPermissionController::class, 'show'])->name('admin.manager-permissions');
    Route::post('/admin/grant-product-access', [ProductPermissionController::class, 'grantAccess'])->name('admin.give-product-access');
    Route::post('/admin/revoke-product-access', [ProductPermissionController::class, 'revokeAccess'])->name('admin.revoke-product-access');
});




// MULTIPLE SHOPS ROUTES
Route::middleware(['auth'])->group(function () {
    Route::get('/shops/create', [ShopController::class, 'index'])->name('shops.create');
    // Route::get('/shops/create', [ShopController::class, 'create'])->name('shops.create');
    Route::post('/shops', [ShopController::class, 'store'])->name('shops.store');
    Route::get('/shops/{id}/edit', [ShopController::class, 'edit'])->name('shops.edit');
    Route::put('/shops/{id}', [ShopController::class, 'update'])->name('shops.update');
    Route::delete('/shops/{id}', [ShopController::class, 'destroy'])->name('shops.destroy');
});


// STOCK TRANSFER

// Route to display the stock transfer form
Route::get('/stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');


Route::get('/products-by-shop/{shopId}', [StockTransferController::class, 'getProductsByShop']);
// Route::get('/products-by-shop/{shopId}', [App\Http\Controllers\ProductController::class, 'getProductsByShop']);

Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');


Route::get('/receipt/search', [PurchaseItemController::class, 'searchReceipt'])->name('receipt.search');
Route::get('/api/product-stock/{id}', [ProductController::class, 'getStock']);




// ROUTES FOR EXPENSES
Route::get('/Adminexpenses', [ExpenseController::class, 'index'])->name('expenses.index');
Route::get('/Adminexpenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
Route::post('/Adminexpenses', [ExpenseController::class, 'store'])->name('expenses.store');
Route::delete('/Adminexpenses/{id}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');



Route::get('/Cashierexpense', [ExpenseController::class, 'indexcash'])->name('cashierexpense.index');
Route::get('/Cashierexpenses/create', [ExpenseController::class, 'createcash'])->name('cashierexpense.create');
Route::post('/Cashierexpenses', [ExpenseController::class, 'storecash'])->name('cashierexpense.store');
Route::delete('/Cashierexpenses/{id}', [ExpenseController::class, 'destroycash'])->name('cashierexpense.destroy');




Route::get('/Managerexpense', [ExpenseController::class, 'indexmanager'])->name('managerexpense.index');
Route::get('/Managerexpenses/create', [ExpenseController::class, 'createmanager'])->name('managerexpense.create');
Route::post('/Managerexpenses', [ExpenseController::class, 'storemanager'])->name('managerexpense.store');
Route::delete('/Managerexpenses/{id}', [ExpenseController::class, 'destroymanager'])->name('managerexpense.destroy');

//ROUTE FOR CREATE CUSTOMER

// Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function() {
//     Route::get('/customers', [CustomerController::class, 'index'])->name('admin.customers.index');
//     Route::get('/customers/create', [CustomerController::class, 'create'])->name('admin.customers.create');
//     Route::post('/customers', [CustomerController::class, 'store'])->name('admin.customers.store');
//     Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('admin.customers.destroy');
// });

// // Manager Routes
// Route::prefix('manager')->middleware(['auth', 'role:manager'])->group(function() {
//     Route::get('/customers', [CustomerController::class, 'index'])->name('manager.customers.index');
//     Route::get('/customers/create', [CustomerController::class, 'create'])->name('manager.customers.create');
//     Route::post('/customers', [CustomerController::class, 'store'])->name('manager.customers.store');
//     Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('manager.customers.destroy');
// });

// Admin
Route::prefix('admin')->middleware(['auth','role:admin'])->group(function(){
    Route::get('/customers', [CustomerController::class,'index'])->name('admin.customers.index');
    Route::post('/customers', [CustomerController::class,'store'])->name('admin.customers.store');
    Route::delete('/customers/{customer}', [CustomerController::class,'destroy'])->name('admin.customers.destroy');
});

// Manager
Route::prefix('manager')->middleware(['auth','role:manager'])->group(function(){
    Route::get('/customers', [CustomerController::class,'index'])->name('manager.customers.index');
    Route::post('/customers', [CustomerController::class,'store'])->name('manager.customers.store');
    Route::delete('/customers/{customer}', [CustomerController::class,'destroy'])->name('manager.customers.destroy');
});

// cashier


    Route::get('/customers', [CustomerController::class,'index'])->name('cashier.customers.index');
    Route::post('/customers', [CustomerController::class,'store'])->name('cashier.customers.store');





// Route::get('/manager/products', [ProductController::class, 'managerProducts']);

Route::get('/manager/products', [ManagerController::class, 'viewProducts'])->name('manager.product');




//INVOICE ROUTE

// web.php (admin routes)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function() {
    Route::get('/invoices/owing', [InvoiceController::class, 'owing'])->name('admin.invoices.owing');
    Route::get('/invoices/{invoice}/edit-payment', [InvoiceController::class, 'editPayment'])->name('admin.invoices.edit-payment');
    Route::post('/invoices/{invoice}/update-payment', [InvoiceController::class, 'updatePayment'])->name('admin.invoices.update-payment');
});

// manager routes
Route::middleware(['auth', 'role:manager'])->prefix('manager')->group(function() {
    Route::get('/invoices/owing', [InvoiceController::class, 'owing'])->name('manager.invoices.owing');
    Route::get('/invoices/{invoice}/edit-payment', [InvoiceController::class, 'editPayment'])->name('manager.invoices.edit-payment');
    Route::post('/invoices/{invoice}/update-payment', [InvoiceController::class, 'updatePayment'])->name('manager.invoices.update-payment');
});








Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function(){
    Route::get('invoices/create', [InvoiceController::class, 'create'])->name('admin.invoices.create');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('admin.invoices.store');
});

Route::prefix('manager')->middleware(['auth', 'role:manager'])->group(function(){
    Route::get('invoices/create', [InvoiceController::class, 'create'])->name('manager.invoices.create');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('manager.invoices.store');
});

Route::prefix('cashier')->middleware(['auth', 'role:cashier'])->group(function(){
    
    Route::get('/invoices/owing', [InvoiceController::class, 'owing'])->name('cashier.invoices.owing');
    Route::get('invoices/create', [InvoiceController::class, 'create'])->name('cashier.invoices.create');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('cashier.invoices.store');
    Route::get('/invoices/{invoice}/edit-payment', [InvoiceController::class, 'editPaymentcash'])->name('cashier.invoices.edit-payment');
    Route::post('/invoices/{invoice}/update-payment', [InvoiceController::class, 'updatePaymentcash'])->name('cashier.invoices.update-payment');
});

Route::get('/admin/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])
    ->name('admin.invoices.preview');

Route::get('/admin/invoices/{invoice}/download', [InvoiceController::class, 'download'])
    ->name('admin.invoices.download');

    Route::get('/invoice/share/{invoice}', [InvoiceController::class, 'share'])
    ->name('invoice.share')
    ->middleware('signed');















Route::post('/products/import', [ProductController::class, 'import'])
    ->name('products.import');


















// Admin
Route::prefix('admin')->middleware(['auth','role:admin'])->group(function () {
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('admin.invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('admin.invoices.store');
});

// Manager
Route::prefix('manager')->middleware(['auth','role:manager'])->group(function () {
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('manager.invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('manager.invoices.store');
});



// SALES REPORT
Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->group(function () {

        Route::get('/report/sales-report', 
            [SalesReportController::class, 'index']
        )->name('admin.report.sales_report');

    });

Route::get(
    '/admin/reports/sales-report/pdf',
    [SalesReportController::class, 'downloadPdf']
)->name('admin.report.sales_report.pdf');


// STOCK REPORT

Route::get('/admin/reports/stock', [StockReportController::class, 'index'])
    ->name('admin.report.stock_report');


// routes/web.php
Route::get(
    '/admin/stock-report/pdf',
    [StockReportController::class, 'downloadPdf']
)->name('admin.stock.report.pdf');


Route::get('/admin/report/profit-loss', [ProfitReportController::class, 'profitLoss'])
    ->name('admin.report.profit_loss');


Route::get('/admin/report/profit-loss/download',[ProfitReportController::class, 'downloadProfitGoodsPdf'])->name('admin.report.profit_loss.download');


// Route::post('/complaints', [ComplaintController::class, 'store'])
//     ->middleware('auth');

Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');

Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');