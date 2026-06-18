@php

$inputEntry  = $production->entries->where('entry_type','input')->first();
$outputEntry = $production->entries->where('entry_type','output')->first();
$lossEntry   = $production->entries->where('entry_type','loss')->first();

$extractItems = function($entry) {

    if (!$entry) return [];

    $meta = $entry->meta;

    if (is_string($meta)) {
        $meta = json_decode($meta, true);
    }

    if (!is_array($meta)) return [];

    $items = $meta['items'] ?? [];

    if (is_string($items)) {
        $items = json_decode($items, true) ?? [];
    }

    return is_array($items) ? array_filter($items, fn($i) => is_array($i)) : [];
};

$inputs  = $extractItems($inputEntry);
$outputs = $extractItems($outputEntry);
$losses  = $extractItems($lossEntry);

@endphp

@extends('layouts.adminapp')

@section('admincontent')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            Edit Production Entry - {{ $production->batch_no }}
        </h4>
    </div>

    <hr>

    {{-- ================= INPUTS ================= --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">INPUTS</div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.production_entries.update', $production->id) }}">
                @csrf
                <input type="hidden" name="entry_type" value="input">

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th width="80">
                                <button type="button" class="btn btn-sm btn-primary"
                                        onclick="addRow('inputBody')">+</button>
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
            <form method="POST" action="{{ route('admin.production_entries.update', $production->id) }}">
                @csrf
                <input type="hidden" name="entry_type" value="output">

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th width="80">
                                <button type="button" class="btn btn-sm btn-success"
                                        onclick="addRow('outputBody')">+</button>
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
            <form method="POST" action="{{ route('admin.production_entries.update', $production->id) }}">
                @csrf
                <input type="hidden" name="entry_type" value="loss">

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th width="80">
                                <button type="button" class="btn btn-sm btn-danger"
                                        onclick="addRow('lossBody')">+</button>
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

{{-- ================= JS ================= --}}
<script>

const productOptions = `
<option value="">Select Product</option>
@foreach($products as $product)
    <option value="{{ $product->id }}">
        {{ $product->product_name ?? $product->name }}
    </option>
@endforeach
`;

const unitOptions = `
<option value="">Select Unit</option>
@foreach($units as $unit)
    <option value="{{ $unit->id }}">
        {{ $unit->name }} ({{ $unit->symbol }})
    </option>
@endforeach
`;

function addRow(tableId, item = {}) {

    const index = Date.now() + Math.random();

    const isBOM = tableId === 'inputBody';
    const isOutput = tableId === 'outputBody';

    const itemField = isBOM
        ? `<select name="items[${index}][item_id]" class="form-control product-select">
               ${productOptions}
           </select>`
        : `<input type="text" name="items[${index}][item_name]"
                  class="form-control"
                  value="${item.item_name ?? ''}"
                  placeholder="Enter item name">`;

    const unitField = isOutput
        ? `<select name="items[${index}][unit_id]" class="form-control">
               ${unitOptions}
           </select>`
        : `<input name="items[${index}][unit]"
                  class="form-control"
                  value="${item.unit ?? ''}"
                  placeholder="kg, bags, pcs">`;

    const row = `
        <tr>

            <td>${itemField}</td>

            <td>
                <input type="number" step="0.01"
                       name="items[${index}][quantity]"
                       class="form-control"
                       value="${item.quantity ?? ''}">
            </td>

            <td>
                ${unitField}
            </td>

            <td>
                <input type="number" step="0.01"
                       name="items[${index}][price]"
                       class="form-control"
                       value="${item.price ?? ''}">
            </td>

            <td>
                <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="this.closest('tr').remove()">X</button>
            </td>

        </tr>
    `;

    document.getElementById(tableId).insertAdjacentHTML('beforeend', row);

    // restore product
    if (isBOM && item.item_id) {
        const rowEl = document.getElementById(tableId).lastElementChild;
        const select = rowEl.querySelector('.product-select');
        if (select) select.value = item.item_id;
    }

    // restore unit dropdown
    if (isOutput && item.unit_id) {
        const rowEl = document.getElementById(tableId).lastElementChild;
        const select = rowEl.querySelector('select[name*="[unit_id]"]');
        if (select) select.value = item.unit_id;
    }
}

// ================= LOAD EXISTING DATA =================
const inputs  = @json(array_values($inputs));
const outputs = @json(array_values($outputs));
const losses  = @json(array_values($losses));

if (inputs.length)  inputs.forEach(i => addRow('inputBody', i)); else addRow('inputBody');
if (outputs.length) outputs.forEach(i => addRow('outputBody', i)); else addRow('outputBody');
if (losses.length)  losses.forEach(i => addRow('lossBody', i)); else addRow('lossBody');

</script>

@endsection