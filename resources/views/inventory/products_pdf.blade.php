<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Product Stock</title>
    <style>
        /* DejaVu Sans is used because it contains the ₦ symbol */
        @page { margin: 28px 28px 36px 28px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5px; color: #0f172a; }

        h1 { font-size: 18px; margin: 0 0 2px; }
        .muted { color: #64748b; }
        .head-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .head-table td { vertical-align: top; padding: 0; }
        .right { text-align: right; }

        .filters { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 6px 9px; margin-bottom: 10px; }
        .filters b { color: #163a6b; }
        .chip { display: inline-block; background: #fff; border: 1px solid #cbd5e1; padding: 1px 6px; margin: 2px 3px 0 0; }

        .summary { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 12px; }
        .summary td { border: 1px solid #e2e8f0; padding: 7px 10px; width: 25%; }
        .summary .lbl { color: #64748b; font-size: 7.5px; text-transform: uppercase; }
        .summary .val { font-size: 13px; font-weight: bold; margin-top: 2px; }

        table.list { width: 100%; border-collapse: collapse; }
        table.list th {
            background: #163a6b; color: #fff; font-size: 7.5px; text-transform: uppercase;
            padding: 6px 5px; text-align: left; border: 1px solid #163a6b;
        }
        table.list td { padding: 5px; border: 1px solid #e2e8f0; vertical-align: middle; }
        table.list tr:nth-child(even) td { background: #f8fafc; }
        thead { display: table-header-group; }   /* repeat header on every page */
        tr { page-break-inside: avoid; }

        .c { text-align: center; }
        .r { text-align: right; }
        .b { font-weight: bold; }
        .s-ok  { color: #15803d; font-weight: bold; }
        .s-low { color: #b45309; font-weight: bold; }
        .s-out { color: #b91c1c; font-weight: bold; }
        .tiny { font-size: 7px; color: #64748b; }
    </style>
</head>
<body>

@php
    $fmt = fn($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
@endphp

<table class="head-table">
    <tr>
        <td>
            <h1>{{ $businessName }} — Product Stock Report</h1>
            <div class="muted">
                {{ $filters ? 'Filtered report' : 'All products' }} · Sorted by {{ $sortLabel }}
            </div>
        </td>
        <td class="right muted">
            Generated {{ $generatedAt->format('d M Y, h:i A') }}<br>
            {{ number_format($summary['total']) }} product{{ $summary['total'] == 1 ? '' : 's' }}
        </td>
    </tr>
</table>

@if($filters)
    <div class="filters">
        <b>Filters applied:</b>
        @foreach($filters as $f)<span class="chip">{{ $f }}</span>@endforeach
    </div>
@endif

<table class="summary">
    <tr>
        <td><div class="lbl">Products listed</div><div class="val">{{ number_format($summary['total']) }}</div></td>
        <td><div class="lbl">Low stock</div><div class="val" style="color:#b45309;">{{ number_format($summary['low']) }}</div></td>
        <td><div class="lbl">Out of stock</div><div class="val" style="color:#b91c1c;">{{ number_format($summary['out']) }}</div></td>
        <td><div class="lbl">Stock value (cost)</div><div class="val">₦{{ number_format($summary['value'], 2) }}</div></td>
    </tr>
</table>

<table class="list">
    <thead>
        <tr>
            <th class="c" style="width:3%">#</th>
            <th style="width:19%">Product</th>
            <th style="width:11%">Shop</th>
            <th style="width:11%">Category</th>
            <th class="c" style="width:8%">Stock left</th>
            <th class="c" style="width:8%">Status</th>
            <th class="c" style="width:5%">Sold</th>
            <th class="r" style="width:8%">Price</th>
            <th class="r" style="width:8%">Cost</th>
            <th style="width:7%">Added</th>
            <th style="width:7%">Edited</th>
            <th style="width:7%">Last sold</th>
        </tr>
    </thead>
    <tbody>
    @forelse($products as $i => $p)
        @php
            $qty   = (float) $p->stock_quantity;
            $limit = (float) ($p->stock_limit ?? 0);
            $out   = $qty <= 0;
            $low   = !$out && $limit > 0 && $qty <= $limit;
            $edited = $p->updated_at && $p->created_at && $p->updated_at->gt($p->created_at->copy()->addMinute());
        @endphp
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td>
                <span class="b">{{ $p->name }}</span>
                @if($p->barcode)<br><span class="tiny">{{ $p->barcode }}</span>@endif
            </td>
            <td>{{ $p->shop->name ?? '—' }}</td>
            <td>{{ $p->category->name ?? '—' }}</td>
            <td class="c b">{{ $fmt($qty) }} {{ $p->stock_unit }}</td>
            <td class="c {{ $out ? 's-out' : ($low ? 's-low' : 's-ok') }}">
                {{ $out ? 'Out of stock' : ($low ? 'Low stock' : 'In stock') }}
            </td>
            <td class="c">{{ $fmt($p->total_sold) }}</td>
            <td class="r">₦{{ number_format($p->price, 2) }}</td>
            <td class="r">₦{{ number_format($p->cost_price, 2) }}</td>
            <td>{{ $p->created_at?->format('d M Y') }}<br><span class="tiny">{{ $p->created_at?->format('h:i A') }}</span></td>
            <td>
                @if($edited)
                    {{ $p->updated_at->format('d M Y') }}<br><span class="tiny">{{ $p->updated_at->format('h:i A') }}</span>
                @else
                    <span class="tiny">Never edited</span>
                @endif
            </td>
            <td>
                @if($p->last_sold_at)
                    {{ \Carbon\Carbon::parse($p->last_sold_at)->format('d M Y') }}
                @else
                    <span class="tiny">Not sold yet</span>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="12" class="c muted" style="padding:20px;">No products match these filters.</td></tr>
    @endforelse
    </tbody>
</table>

</body>
</html>