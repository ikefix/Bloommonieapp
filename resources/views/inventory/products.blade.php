@extends('layouts.app')

@section('content')

<style>
    .inv-card { border:1px solid #e5e7eb; border-radius:12px; padding:16px 18px; background:#fff; }
    .inv-card small { color:#6b7280; text-transform:uppercase; letter-spacing:.5px; font-size:11px; }
    .inv-card h4 { margin:6px 0 0; font-weight:700; }
    .inv-table th { font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; white-space:nowrap; }
    .inv-table td, .inv-table th { vertical-align:middle !important; }
    .inv-table td .badge { display:inline-block; }
    .inv-pager nav { display:flex; flex-direction:column; align-items:center; gap:8px; }
    .inv-pager .pagination { margin:0; flex-wrap:wrap; justify-content:center; }
    .inv-pager p.small { margin:0; color:#6b7280; }
    .inv-muted { color:#6b7280; font-size:12px; }
</style>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">Product Stock</h4>
            <div class="inv-muted">When products were added, edited, and how much is left.</div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="inv-card"><small>Total products</small><h4>{{ number_format($summary['total']) }}</h4></div></div>
        <div class="col-6 col-md-3"><div class="inv-card"><small>Low stock</small><h4 class="text-warning">{{ number_format($summary['low']) }}</h4></div></div>
        <div class="col-6 col-md-3"><div class="inv-card"><small>Out of stock</small><h4 class="text-danger">{{ number_format($summary['out']) }}</h4></div></div>
        <div class="col-6 col-md-3"><div class="inv-card"><small>Stock value (cost)</small><h4>₦{{ number_format($summary['value'], 2) }}</h4></div></div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="inv-card mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name or barcode">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Shop</label>
                <select name="shop_id" class="form-select">
                    <option value="">All shops</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected(request('shop_id') == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Stock status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="in"  @selected(request('status') == 'in')>In stock</option>
                    <option value="low" @selected(request('status') == 'low')>Low stock</option>
                    <option value="out" @selected(request('status') == 'out')>Out of stock</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Sort by</label>
                <select name="sort" class="form-select">
                    <option value="newest"    @selected(request('sort','newest') == 'newest')>Newest added</option>
                    <option value="oldest"    @selected(request('sort') == 'oldest')>Oldest added</option>
                    <option value="updated"   @selected(request('sort') == 'updated')>Recently updated</option>
                    <option value="stock_low" @selected(request('sort') == 'stock_low')>Lowest stock</option>
                    <option value="stock_high"@selected(request('sort') == 'stock_high')>Highest stock</option>
                    <option value="name"      @selected(request('sort') == 'name')>Name A–Z</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small mb-1">Added from</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Added to</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>
            <div class="col-md-8 d-flex gap-2 justify-content-md-end">
                <button class="btn text-white" style="background:#163a6b;">Apply filters</button>
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="inv-card p-0">
        <div class="table-responsive">
            <table class="table inv-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Shop / Category</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Sold (total)</th>
                        <th>Price / Cost</th>
                        <th>Date added</th>
                        <th>Last edited</th>
                        <th>Last sold</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($products as $p)
                    @php
                        $qty      = (float) $p->stock_quantity;
                        $limit    = (float) ($p->stock_limit ?? 0);
                        $isOut    = $qty <= 0;
                        $isLow    = !$isOut && $limit > 0 && $qty <= $limit;
                        $wasEdited = $p->updated_at && $p->created_at && $p->updated_at->gt($p->created_at->copy()->addMinute());
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $p->name }}</div>
                            @if($p->barcode)<div class="inv-muted">{{ $p->barcode }}</div>@endif
                        </td>
                        <td>
                            <div>{{ $p->shop->name ?? '—' }}</div>
                            <div class="inv-muted">{{ $p->category->name ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="fw-bold">
                                {{ rtrim(rtrim(number_format($qty, 2), '0'), '.') }}
                                {{ $p->stock_unit }}
                            </div>
                            @if($p->unit_size)
                                <div class="inv-muted">1 {{ $p->stock_unit ?: 'unit' }} = {{ rtrim(rtrim(number_format($p->unit_size, 2), '0'), '.') }}</div>
                            @endif
                            @if($limit > 0)
                                <div class="inv-muted">Alert at {{ $limit }}</div>
                            @endif
                        </td>
                        <td>
                            @if($isOut)
                                <span class="badge bg-danger">Out of stock</span>
                            @elseif($isLow)
                                <span class="badge bg-warning text-dark">Low stock</span>
                            @else
                                <span class="badge bg-success">In stock</span>
                            @endif
                        </td>
                        <td>{{ rtrim(rtrim(number_format($p->total_sold, 2), '0'), '.') }}</td>
                        <td>
                            <div>₦{{ number_format($p->price, 2) }}</div>
                            <div class="inv-muted">Cost ₦{{ number_format($p->cost_price, 2) }}</div>
                        </td>
                        <td>
                            <div>{{ $p->created_at?->format('d M Y') }}</div>
                            <div class="inv-muted">{{ $p->created_at?->format('h:i A') }} · {{ $p->created_at?->diffForHumans() }}</div>
                        </td>
                        <td>
                            @if($wasEdited)
                                <div>{{ $p->updated_at->format('d M Y') }}</div>
                                <div class="inv-muted">{{ $p->updated_at->diffForHumans() }}</div>
                            @else
                                <span class="inv-muted">Never edited</span>
                            @endif
                        </td>
                        <td>
                            @if($p->last_sold_at)
                                <div>{{ \Carbon\Carbon::parse($p->last_sold_at)->format('d M Y') }}</div>
                                <div class="inv-muted">{{ \Carbon\Carbon::parse($p->last_sold_at)->diffForHumans() }}</div>
                            @else
                                <span class="inv-muted">Not sold yet</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">No products found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 inv-pager">
            {{ $products->onEachSide(2)->links('pagination::bootstrap-5') }}
        </div>
    </div>

</div>

@endsection