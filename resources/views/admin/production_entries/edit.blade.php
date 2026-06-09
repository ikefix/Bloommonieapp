@php

$inputEntry  = $production->entries->where('entry_type','input')->first();
$outputEntry = $production->entries->where('entry_type','output')->first();
$lossEntry   = $production->entries->where('entry_type','loss')->first();

$extractItems = function($entry) {

    if (!$entry) {
        return [];
    }

    $meta = $entry->meta;

    if (is_string($meta)) {
        $meta = json_decode($meta, true);
    }

    if (!is_array($meta)) {
        return [];
    }

    $items = $meta['items'] ?? [];

    if (is_string($items)) {
        $items = json_decode($items, true) ?? [];
    }

    if (empty($items)) {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Check if data is already valid
    |--------------------------------------------------------------------------
    */

    $alreadyValid = true;

    foreach ($items as $item) {

        if (
            !isset($item['item_name']) ||
            !isset($item['quantity'])
        ) {
            $alreadyValid = false;
            break;
        }
    }

    if ($alreadyValid) {
        return $items;
    }

    /*
    |--------------------------------------------------------------------------
    | Rebuild malformed data
    |--------------------------------------------------------------------------
    */

    $rebuilt = [];
    $current = [];

    foreach ($items as $chunk) {

        if (!is_array($chunk)) {
            continue;
        }

        if (
            isset($chunk['item_name']) &&
            !empty($current)
        ) {
            $rebuilt[] = $current;
            $current = [];
        }

        $current = array_merge($current, $chunk);

        if (
            isset($current['item_name']) &&
            isset($current['quantity']) &&
            isset($current['unit']) &&
            isset($current['price'])
        ) {
            $rebuilt[] = $current;
            $current = [];
        }
    }

    if (!empty($current)) {
        $rebuilt[] = $current;
    }

    return $rebuilt;
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
        <div class="card-header bg-primary text-white">
            INPUTS
        </div>

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
                            <th>Price</th>
                            <th width="80">
                                <button type="button"
                                        class="btn btn-sm btn-primary"
                                        onclick="addRow('inputBody')">
                                    +
                                </button>
                            </th>
                        </tr>
                    </thead>

                    <tbody id="inputBody"></tbody>
                </table>

                <button class="btn btn-primary w-100">
                    Save Inputs
                </button>
            </form>

        </div>
    </div>

    {{-- ================= OUTPUTS ================= --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white">
            OUTPUTS
        </div>

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
                            <th>Price</th>
                            <th width="80">
                                <button type="button"
                                        class="btn btn-sm btn-success"
                                        onclick="addRow('outputBody')">
                                    +
                                </button>
                            </th>
                        </tr>
                    </thead>

                    <tbody id="outputBody"></tbody>
                </table>

                <button class="btn btn-success w-100">
                    Save Outputs
                </button>
            </form>

        </div>
    </div>

    {{-- ================= LOSSES ================= --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-danger text-white">
            LOSSES
        </div>

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
                            <th>Price</th>
                            <th width="80">
                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        onclick="addRow('lossBody')">
                                    +
                                </button>
                            </th>
                        </tr>
                    </thead>

                    <tbody id="lossBody"></tbody>
                </table>

                <button class="btn btn-danger w-100">
                    Save Losses
                </button>
            </form>

        </div>
    </div>

</div>

{{-- ================= JAVASCRIPT ================= --}}
<script>

function addRow(tableId, item = {})
{
    const row = `
        <tr>

            <td>
                <input
                    name="items[][item_name]"
                    class="form-control"
                    value="${item.item_name ?? ''}"
                    required>
            </td>

            <td>
                <input
                    name="items[][quantity]"
                    class="form-control"
                    value="${item.quantity ?? ''}"
                    required>
            </td>

            <td>
                <input
                    name="items[][unit]"
                    class="form-control"
                    value="${item.unit ?? ''}"
                    placeholder="kg, bags, pcs">
            </td>

            <td>
                <input
                    name="items[][price]"
                    class="form-control"
                    value="${item.price ?? ''}">
            </td>

            <td>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    onclick="this.closest('tr').remove()">
                    X
                </button>
            </td>

        </tr>
    `;

    document
        .getElementById(tableId)
        .insertAdjacentHTML('beforeend', row);
}
const inputs  = @json($inputs);
const outputs = @json($outputs);
const losses  = @json($losses);

if(inputs.length){
    inputs.forEach(item => addRow('inputBody', item));
}else{
    addRow('inputBody');
}

if(outputs.length){
    outputs.forEach(item => addRow('outputBody', item));
}else{
    addRow('outputBody');
}

if(losses.length){
    losses.forEach(item => addRow('lossBody', item));
}else{
    addRow('lossBody');
}

</script>

@endsection