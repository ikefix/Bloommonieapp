@extends('layouts.adminapp')

@section('admincontent')

@php
    $input  = $production->entries->where('entry_type','input')->first();
    $output = $production->entries->where('entry_type','output')->first();
    $loss   = $production->entries->where('entry_type','loss')->first();

    $extractItems = function($entry) {
        if (!$entry) return [];
        $meta = $entry->meta;
        if (is_string($meta)) $meta = json_decode($meta, true);
        if (!is_array($meta)) return [];
        $items = $meta['items'] ?? [];
        if (is_string($items)) $items = json_decode($items, true) ?? [];
        if (empty($items)) return [];

        // Valid if each item has (item_name OR item_id) AND quantity
        if (isset($items[0]) && is_array($items[0]) &&
            (isset($items[0]['item_name']) || isset($items[0]['item_id'])) &&
            isset($items[0]['quantity'])) {
            return $items;
        }

        // Rebuild flat chunks
        $rebuilt = [];
        $current = [];
        foreach ($items as $chunk) {
            if (!is_array($chunk)) continue;
            if ((isset($chunk['item_name']) || isset($chunk['item_id'])) && !empty($current)) {
                $rebuilt[] = $current;
                $current   = [];
            }
            $current = array_merge($current, $chunk);
        }
        if (!empty($current)) $rebuilt[] = $current;

        return $rebuilt;
    };

    $inputs  = $extractItems($input);
    $outputs = $extractItems($output);
    $losses  = $extractItems($loss);
@endphp

<div class="container">

    {{-- ================= HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Batch Details</h4>
            <small class="text-muted">{{ $production->batch_no }}</small>
        </div>
        <a href="{{ route('admin.production_entries.edit', $production->id) }}" class="btn btn-primary">
            <i class="bx bx-edit"></i> Edit Batch
        </a>
    </div>

    {{-- ================= INPUTS ================= --}}
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">INPUTS (BOM)</div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inputs as $item)
                        @continue(!is_array($item) || (empty($item['item_name']) && empty($item['item_id'])))
                        <tr>
                            <td>
                                @if(!empty($item['item_name']))
                                    {{ $item['item_name'] }}
                                @elseif(!empty($item['item_id']))
                                    {{ \App\Models\Product::find($item['item_id'])?->name ?? 'Unknown' }}
                                @endif
                            </td>
                            <td>{{ $item['quantity'] ?? '-' }}</td>
                            <td>{{ $item['unit'] ?? '-' }}</td>
                            <td>₦{{ number_format((float) str_replace(',', '', $item['price'] ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted text-center py-3">No inputs recorded</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= OUTPUTS ================= --}}
    <div class="card mb-3">
        <div class="card-header bg-success text-white">OUTPUTS</div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($outputs as $item)
                        @continue(!is_array($item) || empty($item['item_name']))
                        <tr>
                            <td>{{ $item['item_name'] }}</td>
                            <td>{{ $item['quantity'] ?? '-' }}</td>
                            <td>{{ $item['unit'] ?? '-' }}</td>
                            <td>₦{{ number_format((float) str_replace(',', '', $item['price'] ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted text-center py-3">No outputs recorded</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= LOSSES ================= --}}
    <div class="card mb-3">
        <div class="card-header bg-danger text-white">LOSSES</div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($losses as $item)
                        @continue(!is_array($item) || empty($item['item_name']))
                        <tr>
                            <td>{{ $item['item_name'] }}</td>
                            <td>{{ $item['quantity'] ?? '-' }}</td>
                            <td>{{ $item['unit'] ?? '-' }}</td>
                            <td>₦{{ number_format((float) str_replace(',', '', $item['price'] ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted text-center py-3">No losses recorded</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection