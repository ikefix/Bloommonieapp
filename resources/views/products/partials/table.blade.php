<div class="table-wrapper">
<table class="table table-bordered table-hover">
    <thead class="thead-dark">
        <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Selling Price (₦)</th>
            <th>Cost Price (₦)</th>
            <th>Stock Quantity</th>
            <th>Shop</th>
            <th>Stock Unit</th>
            <th>Unit Size</th>
            <th>Actions</th>
            <th>Barcode</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($products as $product)
            <tr id="product-row-{{ $product->id }}">
                <td>{{ $product->name }}</td>
                <td>{{ $product->category->name ?? 'N/A' }}</td>
                <td>{{ number_format($product->price, 2) }}</td>
                <td>{{ number_format($product->cost_price, 2) }}</td>
                <td>{{ $product->stock_quantity }}</td>
                <td>{{ $product->shop->name ?? 'Not assigned' }}</td>
                
                <td>

                    @if($product->stock_unit)

                        <div>
                            <strong>
                                {{ $product->stock_unit }}
                            </strong>
                        </div>

                        @if($product->unit_size)

                            @php
                                $fullUnits = floor(
                                    $product->stock_quantity /
                                    $product->unit_size
                                );

                                $pieces =
                                    $product->stock_quantity %
                                    $product->unit_size;
                            @endphp

                            <small class="badge bg-light text-dark mt-1">

                                {{ $fullUnits }}

                                {{ Str::plural(
                                    $product->stock_unit,
                                    $fullUnits
                                ) }}

                                @if($pieces > 0)
                                    + {{ $pieces }} Pieces
                                @endif

                            </small>

                        @endif

                    @else

                        <span class="text-muted">
                            Not Assigned
                        </span>

                    @endif

                </td>
                <td>{{ $product->unit_size ?? 'Not assigned' }}</td>
                <td class="product-btn">
                    <button type="button" class="btn btn-sm btn-warning edit-btn"
                        data-id="{{ $product->id }}"
                        data-name="{{ $product->name }}"
                        data-category="{{ $product->category_id }}"
                        data-price="{{ $product->price }}"
                        data-cost="{{ $product->cost_price }}"
                        data-stock="{{ $product->stock_quantity }}"
                        data-limit="{{ $product->stock_limit }}">
                        Edit
                    </button>

                    <form action="{{ route('products.destroy', $product->id) }}" method="POST" style="display:inline-block;" class="delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">
                            Delete
                        </button>
                    </form>
                </td>
                <td>{{ $product->barcode ?? 'Not assigned' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center">No products found.</td></tr>
        @endforelse
    </tbody>
</table>

</div>

