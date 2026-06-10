@extends('layouts.adminapp')

@section('admincontent')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4 class="mb-0">
            Fill Production Entry - {{ $production->batch_no }}
        </h4>

        @if(session('success'))
            <div class="alert alert-success py-1 px-3 mb-0">
                {{ session('success') }}
            </div>
        @endif

    </div>

    <hr>

    {{-- ================= INPUTS ================= --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">B O M</div>

        <div class="card-body">

            <form method="POST" action="{{ route('admin.production_entries.store', $production->id) }}">
                @csrf
                <input type="hidden" name="entry_type" value="input">

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Cost</th>
                            <th width="80">
                                <button type="button" class="btn btn-sm btn-primary"
                                        onclick="addRow('inputBody','input')">+</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="inputBody"></tbody>
                </table>

                <button class="btn btn-primary w-100">Save Inputs</button>
            </form>

        </div>
    </div>

    {{-- ================= OUTPUTS ================= --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white">OUTPUTS</div>

        <div class="card-body">

            <form method="POST" action="{{ route('admin.production_entries.store', $production->id) }}">
                @csrf
                <input type="hidden" name="entry_type" value="output">

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Cost</th>
                            <th width="80">
                                <button type="button" class="btn btn-sm btn-success"
                                        onclick="addRow('outputBody','output')">+</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="outputBody"></tbody>
                </table>

                <button class="btn btn-success w-100">Save Outputs</button>
            </form>

        </div>
    </div>

    {{-- ================= LOSSES ================= --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-danger text-white">LOSSES</div>

        <div class="card-body">

            <form method="POST" action="{{ route('admin.production_entries.store', $production->id) }}">
                @csrf
                <input type="hidden" name="entry_type" value="loss">

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Cost</th>
                            <th width="80">
                                <button type="button" class="btn btn-sm btn-danger"
                                        onclick="addRow('lossBody','loss')">+</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="lossBody"></tbody>
                </table>

                <button class="btn btn-danger w-100">Save Losses</button>
            </form>

        </div>
    </div>

</div>

{{-- ================= JAVASCRIPT ================= --}}
<script>

const productOptions = `
<option value="">Select Product</option>
@foreach($products as $product)
    <option value="{{ $product->id }}">
        {{ $product->product_name ?? $product->name }}
    </option>
@endforeach
`;

let rowIndex = 0;

function addRow(tableId, type) {
    const index = rowIndex++;
    const isBOM = type === 'input';

    const itemField = isBOM
        ? `<select name="items[${index}][item_id]" class="form-control product-select">
               ${productOptions}
           </select>`
        : `<input type="text" name="items[${index}][item_name]" 
                  class="form-control" placeholder="Enter item name">`;

    const row = `
        <tr data-type="${type}">
            <td>${itemField}</td>
            <td>
                <input type="number" step="0.01"
                       name="items[${index}][quantity]"
                       class="form-control quantity-input">
            </td>
            <td>
                <input name="items[${index}][unit]"
                       class="form-control" placeholder="kg, bags, pcs">
            </td>
            <td>
                <input type="number" step="0.01"
                       name="items[${index}][price]"
                       class="form-control price-input"
                       ${isBOM ? 'readonly placeholder="Auto-calculated"' : 'placeholder="Enter cost"'}>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="this.closest('tr').remove()">X</button>
            </td>
        </tr>
    `;

    document.getElementById(tableId).insertAdjacentHTML('beforeend', row);
}

// AUTO CALC — BOM ONLY
document.addEventListener('input', function (e) {
    const row = e.target.closest('tr');
    if (!row) return;

    const tbody = row.closest('tbody');
    if (!tbody || tbody.id !== 'inputBody') return; // 👈 Only BOM fires fetch

    const productSelect = row.querySelector('.product-select');
    const quantityInput = row.querySelector('.quantity-input');
    const priceInput    = row.querySelector('.price-input');

    if (!productSelect || !quantityInput || !priceInput) return;

    const productId = productSelect.value;
    const quantity  = parseFloat(quantityInput.value || 0);

    if (!productId || quantity <= 0) {
        priceInput.value = '';
        return;
    }

    fetch(`/products/${productId}/price`)
        .then(res => res.json())
        .then(data => {
            const cost = parseFloat(data.cost_price || 0);
            priceInput.value = (cost * quantity).toFixed(2);
        })
        .catch(() => { priceInput.value = ''; });
});

addRow('inputBody', 'input');
addRow('outputBody', 'output');
addRow('lossBody', 'loss');

</script>

@endsection