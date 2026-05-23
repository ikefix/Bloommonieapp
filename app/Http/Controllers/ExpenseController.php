<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
public function index()
{
    $expenses = Expense::latest()->paginate(10); // fetch with pagination
    return view('expenses.index', compact('expenses'));
}



    public function create()
    {
        return view('expenses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        Expense::create([
            'shop_id' => $request->shop_id,
            'title' => $request->title,
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => $request->date,
            'added_by' => Auth::user()->name ?? 'Cashier/Admin',
        ]);


        return redirect()->route('expenses.index')->with('success', 'Expense added successfully!');
    }

    public function destroy($id)
    {
        Expense::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Expense deleted!');
    }















public function indexcash()
{
    $expenses = Expense::where('added_by', Auth::user()->id)  // ✅ ID not name
                        ->latest()
                        ->paginate(10);

    return view('cashierexpense.index', compact('expenses'));
}




    public function createcash()
    {
        return view('cashierexpense.create');
    }

    public function storecash(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        Expense::create([
            'shop_id' => $request->shop_id,
            'title' => $request->title,
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => $request->date,
            'added_by'    => Auth::user()->id,  
        ]);


        return redirect()->route('cashierexpense.index')->with('success', 'Expense added successfully!');
    }

    public function destroycash($id)
    {
        Expense::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Expense deleted!');
    }


















    public function indexmanager()
{
    $expenses = Expense::latest()->paginate(10); // fetch with pagination
    return view('managerexpense.index', compact('expenses'));
}



    public function createmanager()
    {
        return view('managerexpense.create');
    }

    public function storemanager(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        Expense::create([
            'shop_id' => $request->shop_id,
            'title' => $request->title,
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => $request->date,
            'added_by' => Auth::user()->name ?? 'Cashier/Admin',
        ]);


        return redirect()->route('managerexpense.index')->with('success', 'Expense added successfully!');
    }

    public function destroymanager($id)
    {
        Expense::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Expense deleted!');
    }
}











