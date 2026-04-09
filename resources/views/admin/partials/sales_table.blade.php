@php
    $grandTotal = $sales->sum(function ($sale) {
        return (float) $sale->total_price;
    });
@endphp

<h1 style="margin-bottom: 20px; font-size: 24px; color: #28a745; text-align:center;">
    Total Sales: ₦{{ number_format($grandTotal, 2) }}
</h1>

<table class="table table-bordered" id="salesTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th>Category</th>
            <th>Shop</th>
            <th>Quantity</th>
            <th>Total Price</th>
            <th>Discount</th>
            <th>Date</th>
            <th>Action</th> {{-- NEW --}}
            <th>Sales type</th> {{-- NEW --}}
            <th>Cashier name</th> {{-- NEW --}}
        </tr>
    </thead>
    <tbody>
        @forelse($sales as $sale)
            <tr id="sale-{{ $sale->id }}">
                <td>{{ $loop->iteration }}</td>
                <td>{{ $sale->product->name ?? 'N/A' }}</td>
                <td>{{ $sale->product->category->name ?? 'N/A' }}</td>
                <td>{{ $sale->shop->name ?? 'N/A' }}</td>
                <td>{{ $sale->quantity }}</td>
                <td>₦{{ number_format($sale->total_price, 2) }}</td>
                <td>₦{{ number_format($sale->discount ?? 0, 2) }}</td>
                <td>{{ $sale->created_at->format('d M Y H:i') }}</td>

                {{-- DELETE BUTTON --}}
                <td>
                    <button class="btn btn-danger btn-sm delete-sale"
                        data-id="{{ $sale->id }}">
                        Restore
                    </button>
                </td>
                <td>{{ $sale->sale_type }}</td>
                <td>{{ $sale->cashier->name ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">No sales found</td>
            </tr>
        @endforelse
    </tbody>
</table>
