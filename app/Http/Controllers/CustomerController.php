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


// Show edit customer form
public function edit(Customer $customer)
{
    $role = auth()->user()->role;

    $view = match ($role) {
        'admin'   => 'admin.customers.edit',
        'manager' => 'manager.customers.edit',
        default   => 'cashier.customers.edit',
    };

    return view($view, compact('customer'));
}


// Update customer details
public function update(Request $request, Customer $customer)
{
    $request->validate([
        'name'    => 'nullable|string|max:255',
        'email'   => 'nullable|email|unique:customers,email,' . $customer->id,
        'phone'   => 'nullable|string|max:20',
        'address' => 'nullable|string|max:255',
        'company' => 'nullable|string|max:255',
        'notes'   => 'nullable|string|max:500',
    ]);

    $customer->update($request->only([
        'name',
        'email',
        'phone',
        'address',
        'company',
        'notes'
    ]));

    $redirect = match (auth()->user()->role) {
        'admin'   => route('admin.customers.index'),
        'manager' => route('manager.customers.index'),
        default   => route('cashier.customers.index'),
    };

    return redirect($redirect)
        ->with('success', 'Customer updated successfully!');
}




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
