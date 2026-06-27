<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    // GET ALL CUSTOMERS
    public function index()
    {
        $customers = Customer::latest()->get();

        return response()->json([
            'status' => true,
            'data'   => $customers,
        ]);
    }

    // CREATE CUSTOMER
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

        $customer = Customer::create($request->only([
            'name', 'email', 'phone', 'address', 'company', 'notes'
        ]));

        return response()->json([
            'status'  => true,
            'message' => 'Customer created successfully',
            'data'    => $customer,
        ]);
    }

    // SHOW SINGLE CUSTOMER
    public function show($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status'  => false,
                'message' => 'Customer not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $customer,
        ]);
    }

    // UPDATE CUSTOMER
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status'  => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $request->validate([
            'name'    => 'nullable|string|max:255',
            'email'   => 'nullable|email|unique:customers,email,' . $id,
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'notes'   => 'nullable|string|max:500',
        ]);

        $customer->update($request->only([
            'name', 'email', 'phone', 'address', 'company', 'notes'
        ]));

        return response()->json([
            'status'  => true,
            'message' => 'Customer updated successfully',
            'data'    => $customer->fresh(),
        ]);
    }

    // DELETE CUSTOMER
    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status'  => false,
                'message' => 'Customer not found',
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Customer deleted successfully',
        ]);
    }

    // SEARCH CUSTOMERS
    public function search(Request $request)
    {
        $query = $request->input('query');

        $customers = Customer::when($query, function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('company', 'like', "%{$query}%");
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $customers,
        ]);
    }
}