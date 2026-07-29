<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        th { background: #f2f2f2; }
        h2 { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>

<h2>Stock / Inventory Report</h2>

<table>
    <thead>
        <tr>
            <th>Product</th>
            <th>Shop</th>
            <th>Opening</th>
            <th>Added</th>
            <th>Sold</th>
            <th>Remaining</th>
            <th>Stock Unit</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->shop->name ?? '-' }}</td>
                <td>{{ $product->opening_stock }}</td>
                <td>{{ $product->stock_added }}</td>
                <td>{{ $product->stock_sold }}</td>
                <td>{{ $product->remaining_stock }}</td>
                <td>
                    @if($product->stock_unit)

                    <div>
                        <strong>
                            {{ $product->stock_unit }}
                        </strong>
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

                                <small class="">

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
                    </div>
                </td>
                <td>
                    {{ $product->remaining_stock <= $product->stock_limit ? 'LOW' : 'OK' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>

