<?php

namespace App\Http\Controllers;

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
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('cashier-dashboard')->with('error', 'Unauthorized Access');
        }

        $users = User::where('role', '!=', 'admin')->get(); // Exclude admin users
        return view('admin.dashboard', compact('users'));
    }

    /**
     * Update the user's role.
     */
    public function updateRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:cashier,manager,admin',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->role = $request->role;
        $user->save();

        return redirect()->route('admin.users')->with('success', 'User role updated successfully.');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting the currently logged-in admin
        if ($user->id == auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete your own account.');
        }

        // Perform the deletion
        $user->delete();

        return redirect()->route('admin.manage_roles')->with('success', 'User deleted successfully.');
    }


    /**
     * Show the staff registration form (Only accessible by admin).
     */
    public function showRegisterForm()
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('cashier-dashboard')->with('error', 'Unauthorized Access');
        }

        $shops = Shop::all(); // or filter to specific ones if needed
        return view('admin.register', compact('shops'));
    }

    /**
     * Store new staff details (Admin registers new staff).
     */
    public function storeStaff(Request $request)
    {
        // Validate the input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:cashier,manager',
            'shop_id' => 'required_unless:role,admin|exists:shops,id',
        ]);

$ownerId = auth()->user()->getOwnerId();

$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => bcrypt($request->password),
    'role' => $request->role,
    'shop_id' => $request->shop_id,
    'owner_id' => $ownerId,
]);
    
        return redirect()->route('admin.register')->with('success', 'Staff registered successfully.');
    }
    

    /**
     * Show the admin's profile edit form.
     */
    public function editProfile()
    {
        $admin = Auth::user(); // Get the currently logged-in admin
        return view('admin.profile', compact('admin'));
    }

    /**
     * Handle admin profile update.
     */
    public function updateProfile(Request $request)
    {
        $admin = Auth::user(); // Get logged-in admin

        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->id, // Ignore current admin's email
            'password' => 'nullable|min:6|confirmed'
        ]);

        // Update name and email
        $admin->name = $request->name;
        $admin->email = $request->email;

        // If password is provided, update it
        if ($request->password) {
            $admin->password = bcrypt($request->password);
        }

        $admin->save();

        return redirect()->route('admin.profile')->with('success', 'Profile updated successfully.');
    }

    /**
     * Fetch notifications for the admin.
     * This is where we fetch notifications from the database.
     */
    public function notifications()
    {
        $admin = Auth::user();  // Get the logged-in admin
        $notifications = $admin->notifications()->latest()->get();  // Get all notifications (you can also add filtering here)

        // Return as JSON response or use view if you're displaying them on the page
        return response()->json($notifications);
    }

    /**
     * Show the notifications page for the admin.
     */
    public function getNotifications()
    {
        $admin = Auth::user();

        // Fetch unread notifications (or all notifications) for the admin
        $notifications = $admin->notifications;

        return view('admin.notifications', compact('notifications'));
    }

    public function sales()
    {
        // Fetch all sales (purchase items)
        $sales = PurchaseItem::with('product', 'product.category') // Assuming you want product and category details
                             ->orderBy('created_at', 'desc') // Optional: Sort by most recent first
                             ->get();

        // Pass sales data to the view
        return view('admin.sales', compact('sales'));
    }
    



public function salesPage(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $shops = Shop::all(); // ✅ Get all shops for dropdown

        $sales = PurchaseItem::with(['product.category', 'shop'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.sales', compact('sales', 'date', 'shops'));
    }







public function filterSales(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $search = $request->input('search');
        $shopId = $request->input('shop'); // ✅ Get shop filter

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

        return view('admin.partials.sales_table', compact('sales'))->render();
    }



public function dashboard()
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

    return view('admin.dashboard', compact(
        'totalSalesThisWeek',
        'totalRevenueToday',
        'productsInStock',
        'topSelling',
        'topSellingProductNames',
        'topSellingProductSales',
        'salesTrendLabels',
        'salesTrendData',
        'totalDiscountToday',
        'totalDiscountThisWeek',
        'totalDiscountThisMonth',
        'dailyProfit',
        'weeklyProfit',
        'monthlyProfit',
        'dailyExpenses',
        'weeklyExpenses',
        'monthlyExpenses',
        'netProfitToday',
        'netProfitWeek',
        'netProfitMonth',
        'dailyLoss',
        'weeklyLoss',
        'monthlyLoss'
    ));
}









}

