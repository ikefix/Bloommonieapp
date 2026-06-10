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
                <td>{{ $product->stock_unit ?? 'Not assigned' }}</td>
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

<div class="d-flex justify-content-center mt-3">
    @if ($products->hasPages())
        <nav>
            <ul class="pagination">
                {{-- Prev Button --}}
                <li class="page-item {{ $products->onFirstPage() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $products->previousPageUrl() }}" tabindex="-1">
                        ⬅️ Prev
                    </a>
                </li>

                {{-- Page Numbers --}}
                @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                    <li class="page-item {{ $products->currentPage() == $page ? 'active' : '' }}">
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    </li>
                @endforeach

                {{-- Next Button --}}
                <li class="page-item {{ !$products->hasMorePages() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $products->nextPageUrl() }}">
                        Next ➡️
                    </a>
                </li>
            </ul>
        </nav>
    @endif
</div>

