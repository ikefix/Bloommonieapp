@extends('layouts.adminapp')

@section('admincontent')

<style>
    .ps-page { --ink:#0f172a; --muted:#64748b; --line:#e8ecf2; --soft:#f6f8fb; --brand:#163a6b;
               --ok:#15803d; --ok-bg:#dcfce7; --warn:#b45309; --warn-bg:#fef3c7; --bad:#b91c1c; --bad-bg:#fee2e2;
               color:var(--ink); }

    /* header */
    .ps-head h4 { font-weight:800; letter-spacing:-.2px; margin:0; }
    .ps-head p  { margin:2px 0 0; color:var(--muted); font-size:13px; }

    /* summary */
    .ps-stat { display:flex; align-items:center; gap:14px; background:#fff; border:1px solid var(--line);
               border-radius:14px; padding:16px 18px; height:100%; }
    .ps-stat-ico { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
    .ps-stat small { display:block; color:var(--muted); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.6px; }
    .ps-stat b { display:block; font-size:22px; line-height:1.2; font-weight:800; }

    /* filters */
    .ps-filters { background:#fff; border:1px solid var(--line); border-radius:14px; padding:16px; }
    .ps-filters label { font-size:12px; font-weight:600; color:var(--muted); margin-bottom:4px; }
    .ps-filters .form-control, .ps-filters .form-select { border-radius:10px; border-color:var(--line); font-size:14px; }
    .ps-btn { background:var(--brand); color:#fff; border:0; border-radius:10px; padding:8px 18px; font-weight:600; }
    .ps-btn:hover { color:#fff; filter:brightness(1.15); }
    .ps-btn-ghost { border:1px solid var(--line); background:#fff; color:var(--ink); border-radius:10px; padding:8px 18px; font-weight:600; text-decoration:none; }

    /* table */
    .ps-box { background:#fff; border:1px solid var(--line); border-radius:14px; overflow:hidden; }
    .ps-scroll { overflow-x:auto; }
    table.ps-table { width:100%; min-width:1080px; border-collapse:separate; border-spacing:0; margin:0; }
    .ps-table thead th {
        background:var(--soft); color:var(--muted); font-size:11px; font-weight:700; text-transform:uppercase;
        letter-spacing:.7px; padding:12px 16px; border-bottom:1px solid var(--line); white-space:nowrap; text-align:left;
    }
    .ps-table tbody td {
        padding:14px 16px !important; border-bottom:1px solid var(--line); vertical-align:middle !important;
        background:#fff; font-size:14px; text-align:left;
    }
    .ps-table tbody tr:last-child td { border-bottom:0; }
    .ps-table tbody tr:hover td { background:#fafbfd; }
    .ps-table .c-num { text-align:center !important; }
    .ps-table thead th.c-num { text-align:center; }

    .ps-name { font-weight:700; line-height:1.3; }
    .ps-sub  { color:var(--muted); font-size:12px; margin-top:2px; }

    /* stock cell: quantity on one line, status pill on its own line */
    .ps-qty { font-size:18px; font-weight:800; line-height:1.1; }
    .ps-qty span { font-size:12px; font-weight:600; color:var(--muted); margin-left:3px; }
    .ps-pill { display:inline-flex; align-items:center; gap:6px; margin-top:8px; padding:3px 10px; border-radius:999px;
               font-size:11px; font-weight:700; line-height:1.6; }
    .ps-pill::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
    .ps-pill.ok   { background:var(--ok-bg);   color:var(--ok); }
    .ps-pill.low  { background:var(--warn-bg); color:var(--warn); }
    .ps-pill.out  { background:var(--bad-bg);  color:var(--bad); }

    /* left accent on problem rows */
    .ps-table tr.is-out td:first-child { box-shadow:inset 3px 0 0 #ef4444; }
    .ps-table tr.is-low td:first-child { box-shadow:inset 3px 0 0 #f59e0b; }

    .ps-sold { display:inline-block; min-width:38px; padding:4px 10px; border-radius:8px; background:var(--soft); font-weight:700; }
    .ps-price { font-weight:700; }
    .ps-date  { font-weight:600; }
    .ps-none  { color:#94a3b8; font-size:13px; }
    .ps-tag-edit { display:inline-block; margin-top:4px; font-size:11px; font-weight:600; color:#1d4ed8; background:#e0ecff; border-radius:6px; padding:1px 8px; }

    /* pagination */
    .ps-pager { padding:16px; border-top:1px solid var(--line); display:flex; justify-content:center; }
    .ps-pager nav { display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:10px 16px; }
    .ps-pager .pagination { margin:0; flex-wrap:wrap; justify-content:center; }
    .ps-pager p.small { margin:0; color:var(--muted); }
</style>

<div class="container-fluid py-4 ps-page">

    <div class="ps-head mb-4">
        <h4>Product Stock</h4>
        <p>When each product was added, last edited, and how much is left.</p>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="ps-stat">
                <div class="ps-stat-ico" style="background:#e0ecff;color:#1d4ed8;"><i class="bi bi-box-seam"></i></div>
                <div><small>Total products</small><b>{{ number_format($summary['total']) }}</b></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ps-stat">
                <div class="ps-stat-ico" style="background:#fef3c7;color:#b45309;"><i class="bi bi-exclamation-triangle"></i></div>
                <div><small>Low stock</small><b>{{ number_format($summary['low']) }}</b></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ps-stat">
                <div class="ps-stat-ico" style="background:#fee2e2;color:#b91c1c;"><i class="bi bi-x-circle"></i></div>
                <div><small>Out of stock</small><b>{{ number_format($summary['out']) }}</b></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ps-stat">
                <div class="ps-stat-ico" style="background:#dcfce7;color:#15803d;"><i class="bi bi-cash-stack"></i></div>
                <div><small>Stock value (cost)</small><b>₦{{ number_format($summary['value'], 2) }}</b></div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="ps-filters mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name or barcode">
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label>Shop</label>
                <select name="shop_id" class="form-select">
                    <option value="">All shops</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" @selected(request('shop_id') == $shop->id)>{{ $shop->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label>Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label>Stock status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="in"  @selected(request('status') == 'in')>In stock</option>
                    <option value="low" @selected(request('status') == 'low')>Low stock</option>
                    <option value="out" @selected(request('status') == 'out')>Out of stock</option>
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label>Sort by</label>
                <select name="sort" class="form-select">
                    <option value="newest"     @selected(request('sort','newest') == 'newest')>Newest added</option>
                    <option value="oldest"     @selected(request('sort') == 'oldest')>Oldest added</option>
                    <option value="updated"    @selected(request('sort') == 'updated')>Recently updated</option>
                    <option value="stock_low"  @selected(request('sort') == 'stock_low')>Lowest stock</option>
                    <option value="stock_high" @selected(request('sort') == 'stock_high')>Highest stock</option>
                    <option value="name"       @selected(request('sort') == 'name')>Name A–Z</option>
                </select>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <label>Added from</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label>Added to</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>
            <div class="col-md-6 col-lg-8 d-flex gap-2 justify-content-md-end">
                <button class="ps-btn">Apply filters</button>
                <a href="{{ url()->current() }}" class="ps-btn-ghost">Reset</a>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="ps-box">
        <div class="ps-scroll">
            <table class="ps-table">
                <thead>
                    <tr>
                        <th style="width:24%">Product</th>
                        <th style="width:15%">Shop / Category</th>
                        <th style="width:13%">Stock left</th>
                        <th class="c-num" style="width:8%">Sold</th>
                        <th style="width:12%">Price / Cost</th>
                        <th style="width:11%">Date added</th>
                        <th style="width:11%">Last edited</th>
                        <th style="width:11%">Last sold</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($products as $p)
                    @php
                        $qty       = (float) $p->stock_quantity;
                        $limit     = (float) ($p->stock_limit ?? 0);
                        $isOut     = $qty <= 0;
                        $isLow     = !$isOut && $limit > 0 && $qty <= $limit;
                        $wasEdited = $p->updated_at && $p->created_at && $p->updated_at->gt($p->created_at->copy()->addMinute());
                        $fmt       = fn($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
                    @endphp
                    <tr class="{{ $isOut ? 'is-out' : ($isLow ? 'is-low' : '') }}">
                        <td>
                            <div class="ps-name">{{ $p->name }}</div>
                            @if($p->barcode)<div class="ps-sub">{{ $p->barcode }}</div>@endif
                        </td>

                        <td>
                            <div style="font-weight:600;">{{ $p->shop->name ?? '—' }}</div>
                            <div class="ps-sub">{{ $p->category->name ?? '—' }}</div>
                        </td>

                        <td>
                            <div class="ps-qty">{{ $fmt($qty) }}<span>{{ $p->stock_unit }}</span></div>
                            @if($isOut)
                                <div class="ps-pill out">Out of stock</div>
                            @elseif($isLow)
                                <div class="ps-pill low">Low stock</div>
                            @else
                                <div class="ps-pill ok">In stock</div>
                            @endif
                            @if($limit > 0)
                                <div class="ps-sub" style="margin-top:6px;">Alert at {{ $fmt($limit) }}</div>
                            @endif
                        </td>

                        <td class="c-num"><span class="ps-sold">{{ $fmt($p->total_sold) }}</span></td>

                        <td>
                            <div class="ps-price">₦{{ number_format($p->price, 2) }}</div>
                            <div class="ps-sub">Cost ₦{{ number_format($p->cost_price, 2) }}</div>
                        </td>

                        <td>
                            <div class="ps-date">{{ $p->created_at?->format('d M Y') }}</div>
                            <div class="ps-sub">{{ $p->created_at?->format('h:i A') }}</div>
                            <div class="ps-sub">{{ $p->created_at?->diffForHumans() }}</div>
                        </td>

                        <td>
                            @if($wasEdited)
                                <div class="ps-date">{{ $p->updated_at->format('d M Y') }}</div>
                                <div class="ps-sub">{{ $p->updated_at->format('h:i A') }}</div>
                                <span class="ps-tag-edit">{{ $p->updated_at->diffForHumans() }}</span>
                            @else
                                <span class="ps-none">Never edited</span>
                            @endif
                        </td>

                        <td>
                            @if($p->last_sold_at)
                                @php $sold = \Carbon\Carbon::parse($p->last_sold_at); @endphp
                                <div class="ps-date">{{ $sold->format('d M Y') }}</div>
                                <div class="ps-sub">{{ $sold->diffForHumans() }}</div>
                            @else
                                <span class="ps-none">Not sold yet</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5" style="color:#94a3b8;">No products found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="ps-pager">
            {{ $products->onEachSide(2)->links('pagination::bootstrap-5') }}
        </div>
    </div>

</div>

@endsection