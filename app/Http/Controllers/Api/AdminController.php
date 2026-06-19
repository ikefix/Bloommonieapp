<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use Illuminate\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use App\Models\Shop;
use App\Models\User;
use App\Models\Expense;
use App\Models\PurchaseItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use Notifiable;

    /**
     * Show the list of users (except the admin).
     */
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized Access',
            ], 403);
        }

        $users = User::where('role', '!=', 'admin')
            ->where('owner_id', $request->user()->id)
            ->get();

        return response()->json([
            'status' => true,
            'users' => $users,
        ]);
    }

    /**
     * Update the user's role.
     */
    public function updateRole(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized Access',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:cashier,manager,admin',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->role = $request->role;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User role updated successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Delete a staff user.
     */
    public function deleteUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting the currently logged-in admin
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

    /**
     * Get shops for staff registration form (dropdown data).
     */
    public function showRegisterForm(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized Access',
            ], 403);
        }

        $shops = Shop::where('user_id', $request->user()->id)->get();

        return response()->json([
            'status' => true,
            'shops' => $shops,
        ]);
    }

    /**
     * Store new staff details (Admin registers new staff).
     */
    public function storeStaff(Request $request)
    {
        if (!$request->user()->canCreateMoreUsers()) {
            return response()->json([
                'status' => false,
                'message' => 'Your current plan has reached its user limit. Upgrade your subscription.',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:cashier,manager',
            'shop_id' => 'required_unless:role,admin|exists:shops,id',
        ]);

        $ownerId = $request->user()->getOwnerId();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'email_verified_at' => now(),
            'role' => $request->role,
            'shop_id' => $request->shop_id,
            'owner_id' => $ownerId,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Staff registered successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Get the admin's profile.
     */
    public function editProfile(Request $request)
    {
        return response()->json([
            'status' => true,
            'admin' => $request->user(),
        ]);
    }

    /**
     * Handle admin profile update.
     */
    public function updateProfile(Request $request)
    {
        $admin = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->id,
            'password' => 'nullable|min:6|confirmed'
        ]);

        $admin->name = $request->name;
        $admin->email = $request->email;

        if ($request->password) {
            $admin->password = bcrypt($request->password);
        }

        $admin->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully.',
            'admin' => $admin,
        ]);
    }

    /**
     * Fetch notifications for the admin.
     */
    public function notifications(Request $request)
    {
        $admin = $request->user();
        $notifications = $admin->notifications()->latest()->get();

        return response()->json([
            'status' => true,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get notifications (all) for the admin.
     */
    public function getNotifications(Request $request)
    {
        $admin = $request->user();
        $notifications = $admin->notifications;

        return response()->json([
            'status' => true,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get all sales (purchase items).
     */
    public function sales()
    {
        $sales = PurchaseItem::with('product', 'product.category')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'sales' => $sales,
        ]);
    }

    /**
     * Sales page data with date + shop list.
     */
    public function salesPage(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $shops = Shop::where('user_id', $request->user()->id)->get();

        $sales = PurchaseItem::with(['product.category', 'shop'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'date' => $date,
            'shops' => $shops,
            'sales' => $sales,
        ]);
    }

    /**
     * Filter sales by date, search term, and shop.
     */
    public function filterSales(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $search = $request->input('search');
        $shopId = $request->input('shop');

        $query = PurchaseItem::with(['product.category', 'shop'])
            ->whereDate('created_at', $date);

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'sales' => $sales,
        ]);
    }

    /**
     * Full admin dashboard analytics.
     */
    public function dashboard(Request $request)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // 💸 Total sales for the week
        $totalSalesThisWeek = PurchaseItem::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum(DB::raw('total_price - COALESCE(discount_value, 0)'));

        // 💰 Revenue today
        $totalRevenueToday = PurchaseItem::whereDate('created_at', $today)
            ->sum('total_price');

        // 🏷️ Discount totals
        $totalDiscountToday = PurchaseItem::whereDate('created_at', $today)->sum('discount_value');
        $totalDiscountThisWeek = PurchaseItem::whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('discount_value');
        $totalDiscountThisMonth = PurchaseItem::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('discount_value');

        // 📦 Products in stock
        $productsInStock = Product::where('stock_quantity', '>', 0)->count();

        // 🧾 Top selling products today
        $topSelling = PurchaseItem::whereDate('created_at', $today)
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->with('product')
            ->take(5)
            ->get();

        // 🥧 Pie chart data
        $topSellingProductNames = [];
        $topSellingProductSales = [];

        foreach ($topSelling as $item) {
            $topSellingProductNames[] = $item->product->name ?? 'Unknown';
            $topSellingProductSales[] = $item->total_sold;
        }

        // 📈 Sales trend
        $salesTrend = PurchaseItem::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_price) as total')
            )
            ->whereDate('created_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $salesTrendLabels = [];
        $salesTrendData = [];

        $dates = collect(range(0, 6))->map(function ($daysAgo) {
            return Carbon::today()->subDays($daysAgo)->format('Y-m-d');
        })->reverse();

        foreach ($dates as $date) {
            $salesTrendLabels[] = Carbon::parse($date)->format('M d');
            $daySale = $salesTrend->firstWhere('date', $date);
            $salesTrendData[] = $daySale ? $daySale->total : 0;
        }

        // 💹 PROFIT BEFORE EXPENSES
        $dailyProfit = PurchaseItem::whereDate('created_at', $today)
            ->with('product')
            ->get()
            ->sum(function ($item) {
                $costPrice = $item->product->cost_price ?? 0;
                $sellingPrice = $item->total_price - ($item->discount_value ?? 0);
                return ($sellingPrice - ($costPrice * $item->quantity));
            });

        $weeklyProfit = PurchaseItem::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->with('product')
            ->get()
            ->sum(function ($item) {
                $costPrice = $item->product->cost_price ?? 0;
                $sellingPrice = $item->total_price - ($item->discount_value ?? 0);
                return ($sellingPrice - ($costPrice * $item->quantity));
            });

        $monthlyProfit = PurchaseItem::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->with('product')
            ->get()
            ->sum(function ($item) {
                $costPrice = $item->product->cost_price ?? 0;
                $sellingPrice = $item->total_price - ($item->discount_value ?? 0);
                return ($sellingPrice - ($costPrice * $item->quantity));
            });

        // 🧾 Expenses
        $dailyExpenses = Expense::whereDate('date', $today)->sum('amount');
        $weeklyExpenses = Expense::whereBetween('date', [$startOfWeek, $endOfWeek])->sum('amount');
        $monthlyExpenses = Expense::whereBetween('date', [$startOfMonth, $endOfMonth])->sum('amount');

        // 💵 NET PROFIT (After Expenses)
        $netProfitToday = $dailyProfit - $dailyExpenses;
        $netProfitWeek = $weeklyProfit - $weeklyExpenses;
        $netProfitMonth = $monthlyProfit - $monthlyExpenses;

        // 📉 Loss (if net profit < 0)
        $dailyLoss = $netProfitToday < 0 ? abs($netProfitToday) : 0;
        $weeklyLoss = $netProfitWeek < 0 ? abs($netProfitWeek) : 0;
        $monthlyLoss = $netProfitMonth < 0 ? abs($netProfitMonth) : 0;

        return response()->json([
            'status' => true,
            'data' => [
                'totalSalesThisWeek' => $totalSalesThisWeek,
                'totalRevenueToday' => $totalRevenueToday,
                'productsInStock' => $productsInStock,
                'topSelling' => $topSelling,
                'topSellingProductNames' => $topSellingProductNames,
                'topSellingProductSales' => $topSellingProductSales,
                'salesTrendLabels' => $salesTrendLabels,
                'salesTrendData' => $salesTrendData,
                'totalDiscountToday' => $totalDiscountToday,
                'totalDiscountThisWeek' => $totalDiscountThisWeek,
                'totalDiscountThisMonth' => $totalDiscountThisMonth,
                'dailyProfit' => $dailyProfit,
                'weeklyProfit' => $weeklyProfit,
                'monthlyProfit' => $monthlyProfit,
                'dailyExpenses' => $dailyExpenses,
                'weeklyExpenses' => $weeklyExpenses,
                'monthlyExpenses' => $monthlyExpenses,
                'netProfitToday' => $netProfitToday,
                'netProfitWeek' => $netProfitWeek,
                'netProfitMonth' => $netProfitMonth,
                'dailyLoss' => $dailyLoss,
                'weeklyLoss' => $weeklyLoss,
                'monthlyLoss' => $monthlyLoss,
            ],
        ]);
    }
}