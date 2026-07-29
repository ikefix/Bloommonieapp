<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    // GET /api/admin/expenses
    public function index()
    {
        $expenses = Expense::latest()->paginate(10);

        return response()->json([
            'success'  => true,
            'expenses' => $expenses,
        ]);
    }

    // POST /api/admin/expenses
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_id'     => 'required|exists:shops,id',
            'title'       => 'required|string|max:255',
            'amount'      => 'required|numeric',
            'description' => 'nullable|string',
            'date'        => 'required|date',
        ]);

        $expense = Expense::create([
            'shop_id'     => $validated['shop_id'],
            'title'       => $validated['title'],
            'amount'      => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'date'        => $validated['date'],
            'added_by'    => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense added successfully!',
            'expense' => $expense,
        ], 201);
    }

    // DELETE /api/admin/expenses/{id}
    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted!',
        ]);
    }

    // GET /api/cashier/expenses
    public function indexcash()
    {
        $expenses = Expense::where('added_by', Auth::id())
            ->latest()
            ->paginate(10);

        return response()->json([
            'success'  => true,
            'expenses' => $expenses,
        ]);
    }

    // POST /api/cashier/expenses
    public function storecash(Request $request)
    {
        $validated = $request->validate([
            'shop_id'     => 'required|exists:shops,id',
            'title'       => 'required|string|max:255',
            'amount'      => 'required|numeric',
            'description' => 'nullable|string',
            'date'        => 'required|date',
        ]);

        $expense = Expense::create([
            'shop_id'     => $validated['shop_id'],
            'title'       => $validated['title'],
            'amount'      => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'date'        => $validated['date'],
            'added_by'    => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense added successfully!',
            'expense' => $expense,
        ], 201);
    }

    // DELETE /api/cashier/expenses/{id}
    public function destroycash($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted!',
        ]);
    }

    // GET /api/manager/expenses
    public function indexmanager()
    {
        $expenses = Expense::latest()->paginate(10);

        return response()->json([
            'success'  => true,
            'expenses' => $expenses,
        ]);
    }

    // POST /api/manager/expenses
    public function storemanager(Request $request)
    {
        $validated = $request->validate([
            'shop_id'     => 'required|exists:shops,id',
            'title'       => 'required|string|max:255',
            'amount'      => 'required|numeric',
            'description' => 'nullable|string',
            'date'        => 'required|date',
        ]);

        $expense = Expense::create([
            'shop_id'     => $validated['shop_id'],
            'title'       => $validated['title'],
            'amount'      => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'date'        => $validated['date'],
            'added_by'    => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense added successfully!',
            'expense' => $expense,
        ], 201);
    }

    // DELETE /api/manager/expenses/{id}
    public function destroymanager($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted!',
        ]);
    }
}