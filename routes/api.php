<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\GoogleLoginController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\FCMTokenController.php;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::post('/register', [RegisterController::class, 'register']);
Route::post('/google-login', [GoogleLoginController::class, 'googleLogin']);
Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LoginController::class, 'logout']);

    // CATEGORIES
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // SHOPS
    Route::get('/shops', [ShopController::class, 'index']);
    Route::post('/shops', [ShopController::class, 'store']);
    Route::put('/shops/{id}', [ShopController::class, 'update']);
    Route::delete('/shops/{id}', [ShopController::class, 'destroy']);

    // ADMIN DASHBOARD
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);

    // PRODUCTS
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // IMPORT
    Route::post('/products/import', [ProductController::class, 'import']);

    // STOCK
    Route::get('/products/{id}/stock', [ProductController::class, 'getStock']);

    // SELL
    Route::post('/products/{id}/sell', [ProductController::class, 'sellProduct']);

    // SEARCH
    Route::get('/products/search/suggestions', [ProductController::class, 'searchSuggestions']);

    // SALES
    Route::get('/admin/sales-page', [AdminController::class, 'salesPage']);
    Route::get('/admin/filter-sales', [AdminController::class, 'filterSales']);
    Route::delete('/admin/sales/{id}', [AdminController::class, 'deleteSale']);

    // STAFF MANAGEMENT
    Route::get('/admin/register-form', [AdminController::class, 'showRegisterForm']);
    Route::post('/admin/store-staff', [AdminController::class, 'storeStaff']);

    // ROLE MANAGEMENT
    Route::get('/admin/users', [RoleController::class, 'index']);
    Route::patch('/admin/users/{id}/role', [RoleController::class, 'updateRole']);
    Route::delete('/admin/users/{id}', [RoleController::class, 'deleteUser']);
    Route::patch('/admin/users/{id}/shop', [RoleController::class, 'updateShop']);

    // INVOICES
    Route::get('/invoices/create',          [InvoiceController::class, 'create']);       // get customers/shops/products
    Route::post('/invoices',                [InvoiceController::class, 'store']);        // create invoice
    Route::get('/invoices',                 [InvoiceController::class, 'owing']);        // all + owing invoices
    Route::get('/invoices/search',          [InvoiceController::class, 'search']);       // search by customer
    Route::get('/invoices/receivables',     [InvoiceController::class, 'receivables']); // grouped by customer
    Route::get('/invoices/{id}',            [InvoiceController::class, 'show']);         // single invoice
    Route::delete('/invoices/{id}',         [InvoiceController::class, 'destroy']);      // delete + return stock
    Route::post('/invoices/{id}/payment',   [InvoiceController::class, 'updatePayment']); // add payment
    Route::post('/invoices/{id}/mark-paid', [InvoiceController::class, 'markPaid']);    // mark fully paid
    Route::get('/invoices/{id}/preview',  [InvoiceController::class, 'preview']);
    Route::get('/invoices/{id}/download', [InvoiceController::class, 'download']);

    // CUSTOMERS
    Route::get('/customers',                [CustomerController::class, 'index']);
    Route::post('/customers',               [CustomerController::class, 'store']);
    Route::get('/customers/search',         [CustomerController::class, 'search']);  // ← specific first
    Route::get('/customers/{id}',           [CustomerController::class, 'show']);
    Route::put('/customers/{id}',           [CustomerController::class, 'update']);
    Route::delete('/customers/{id}',        [CustomerController::class, 'destroy']);

    // NOTIFICATIONS
    Route::get('/notifications',                [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read',     [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all',      [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}',        [NotificationController::class, 'destroy']);
    Route::delete('/notifications',             [NotificationController::class, 'destroyAll']);

    Route::post('/fcm-token', [FCMTokenController::class, 'store']);
});