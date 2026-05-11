<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
class ShopController extends Controller
{
    public function index()
    {
        $shops = Auth::user()->shops ?? collect(); // fallback to empty collection
        return view('shops.create', compact('shops'));
    }
    
    
    // public function create()
    // {
    //     return view('shops.create');
    // }
    
    
    public function store(Request $request)
    {
        if (!auth()->user()->canCreateMoreStores()) {

            return back()->with(
                'error',
                'Your current plan has reached the maximum store limit.'
            );
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);
    
        Shop::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'location' => $request->location,
        ]);
    
        return redirect()->route('shops.create')->with('success', 'Shop created successfully.');
    }

    public function edit($id)
    {
        $shop = Shop::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        return view('shops.edit', compact('shop'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $shop = Shop::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $shop->update($request->only('name', 'location'));

        return redirect()->route('shops.create')->with('success', 'Shop updated successfully.');
    }

    public function destroy($id)
    {
        $shop = Shop::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $shop->delete();

        return redirect()->route('shops.create')->with('success', 'Shop deleted successfully.');
    }



    
}
