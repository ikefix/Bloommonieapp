<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    // Show form + table
public function index()
{
    $customers = Customer::latest()->get(); // fetch all customers

    if (auth()->user()->role === 'admin') {
        $view = 'admin.customers.create';
    } elseif (auth()->user()->role === 'manager') {
        $view = 'manager.customers.create';
    } else {
        $view = 'cashier.customers.create';
    }

    return view($view, compact('customers'));
}


    // Store new customer
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'nullable|email|unique:customers,email',
    //         'phone' => 'nullable|string|max:20',
    //         'address' => 'nullable|string|max:255',
    //         'company' => 'nullable|string|max:255',
    //         'notes' => 'nullable|string|max:500',
    //     ]);

    //     Customer::create($request->only(['name','email','phone','address','company','notes']));

    //     // After storing, redirect back to the index page so the table updates
    //     $redirect = auth()->user()->role === 'admin' ? route('admin.customers.index') : route('manager.customers.index');

    //     return redirect($redirect)->with('success', 'Customer created successfully!');
    // }

    public function store(Request $request)
{
    $request->validate([
        'name'    => 'nullable|string|max:255',
        'email'   => 'nullable|email|unique:customers,email',
        'phone'   => 'nullable|string|max:20',
        'address' => 'nullable|string|max:255',
        'company' => 'nullable|string|max:255',
        'notes'   => 'nullable|string|max:500',
    ]);

    Customer::create($request->only([
        'name', 'email', 'phone', 'address', 'company', 'notes'
    ]));

    $redirect = match(auth()->user()->role) {
        'admin'   => route('admin.customers.index'),
        'manager' => route('manager.customers.index'),
        default   => route('cashier.customers.index'),
    };

    return redirect($redirect)->with('success', 'Customer created successfully!');
}


    // Delete customer
    public function destroy(Customer $customer)
    {
        $customer->delete();

        $role = auth()->user()->role;

        $redirect = match($role) {
            'admin'   => route('admin.customers.index'),
            'manager' => route('manager.customers.index'),
            default   => route('cashier.customers.index'),
        };

        return redirect($redirect)->with('success', 'Customer deleted successfully!');
    }
}
