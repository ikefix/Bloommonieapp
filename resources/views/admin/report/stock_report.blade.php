@extends('layouts.adminapp')

@section('admincontent')

@php
    $user = auth()->user();

    $locked = !$user->hasFeature('stock_report');

    $message = $user->getLimitMessage(
        'feature',
        'Stock / Inventory Report'
    );
@endphp


<style>

.page-lock-wrapper{
    position: relative;
}

.lock-overlay{
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(4px);
    z-index: 99;

    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 14px;
}

.lock-card{
    background: white;
    width: 420px;
    max-width: 90%;
    padding: 35px;
    border-radius: 18px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);

    animation: floatCard 2s ease-in-out infinite;
}

.lock-card i{
    font-size: 60px;
    color: #ff9800;
    margin-bottom: 15px;
}

.lock-card h2{
    font-size: 28px;
    margin-bottom: 10px;
    font-weight: 700;
}

.lock-card p{
    color: #666;
    line-height: 1.6;
    margin-bottom: 25px;
}

.upgrade-btn{
    display: inline-block;
    background: linear-gradient(135deg,#ff9800,#ff5722);
    color: white;
    text-decoration: none;
    padding: 12px 24px;
    border-radius: 50px;
    font-weight: bold;
    transition: .3s;
}

.upgrade-btn:hover{
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(255,87,34,.3);
    color: white;
}

.lock-badge{
    display: inline-block;
    background: rgba(255,152,0,.1);
    color: #ff9800;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 13px;
    margin-bottom: 15px;
    font-weight: 600;
}

@keyframes floatCard{
    0%{
        transform: translateY(0px);
    }

    50%{
        transform: translateY(-6px);
    }

    100%{
        transform: translateY(0px);
    }
}

.locked-content{
    pointer-events: none;
    user-select: none;
}

</style>


<div class="page-lock-wrapper">

    @if($locked)

        <div class="lock-overlay">

            <div class="lock-card">

                <div class="lock-badge">
                    PREMIUM FEATURE
                </div>

                <i class="fas fa-lock"></i>

                <h2>Stock Report Locked</h2>

                <p>
                    {{ $message }}
                </p>

                <a href="{{ url('/pricing') }}" class="upgrade-btn">
                    Upgrade Plan
                </a>

            </div>

        </div>

    @endif

    <div class="{{ $locked ? 'locked-content' : '' }}">
        <div class="container-fluid">

            {{-- PAGE TITLE --}}
            <div class="mb-4">
                <h3 class="fw-bold text-primary">Stock / Inventory Report</h3>
                <p class="text-muted">Track moving, sleeping and low stock items</p>
            </div>

            {{-- FILTERS --}}
            <form method="GET" class="row g-3 mb-4 align-items-end">

                {{-- SEARCH BAR --}}
                <div class="col-md-4">
                    <label class="form-label">Search Product</label>
                    <input
                        type="text"
                        name="search"
                        value=""
                        class="form-control"
                        placeholder="Search by product name..."
                        id="searchInput"
                        autofocus
                    >
                </div>

                {{-- SHOP FILTER --}}
                <div class="col-md-4">
                    <label class="form-label">Shop</label>
                    <select name="shop_id" class="form-select">
                        <option value="">All Shops</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                                {{ $shop->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- APPLY BUTTON --}}
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary">Apply</button>
                </div>

                <div class="col-md-2 d-grid">
            <a
                href="{{ route('admin.stock.report.pdf', request()->query()) }}"
                class="btn btn-dark"
            >
                <i class="bi bi-download"></i> Download PDF
            </a>
        </div>

            </form>

            {{-- SUMMARY CARDS + CHART INLINE --}}
            <div class="row mb-4 g-3 align-items-stretch">

            {{-- TOTAL PRODUCTS --}}
            <div class="col-md-4">
                <div class="card shadow-sm p-3 bg-light h-100 d-flex flex-column justify-content-center text-center">
                    <div class="mb-2">
                        <i class="bi bi-box-seam text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="text-muted">Total Products</h6>
                    <h3 class="fw-bold">{{ $totalProducts }}</h3>
                    <p class="text-muted mb-0">All products in inventory</p>
                </div>
            </div>

            {{-- LOW STOCK --}}
            <div class="col-md-4">
                <div class="card shadow-sm p-3 bg-light h-100 d-flex flex-column justify-content-center text-center">
                    <div class="mb-2">
                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="text-muted">Low Stock Items</h6>
                    <h3 class="fw-bold text-danger">{{ $lowStockCount }}</h3>
                    <p class="text-muted mb-0">Products below stock limit</p>
                </div>
            </div>

            {{-- CHART --}}
            <div class="col-md-4">
                <div class="card shadow-sm p-3 bg-light h-100 d-flex flex-column justify-content-center text-center">
                    <div class="mb-2">
                        <i class="bi bi-graph-up text-success" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="text-muted mb-2">Stock Health Overview</h6>
                    <canvas id="stockChart" height="100"></canvas>
                </div>
            </div>

        </div>


            {{-- STOCK TABLE --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <strong>Stock Breakdown</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Shop</th>
                                <th>Opening Stock</th>
                                <th>Stock Added</th>
                                <th>Stock Sold</th>
                                <th>Remaining</th>
                                <th>Stock Unit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
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
                                        @if($product->remaining_stock <= $product->stock_limit)
                                            <span class="badge bg-danger">Low</span>
                                        @else
                                            <span class="badge bg-success">OK</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No products found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PAGINATION --}}
            <div class="mt-3 d-flex justify-content-center">
                {{ $products->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>

</div>




{{-- CHART.JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('stockChart');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($stockChart['labels']) !!},
        datasets: [{
            data: {!! json_encode($stockChart['data']) !!},
            backgroundColor: ['#dc3545', '#28a745']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

{{-- SEARCH BAR JS: ACTIVE SEARCH, CURSOR STAYS, NO PAGE RELOAD UNTIL SUBMIT --}}
<script>
const searchInput = document.getElementById('searchInput');

if (searchInput) {
    searchInput.focus(); // always focused

    // Keep cursor at end
    // searchInput.addEventListener('keydown', function () {
    //     const val = this.value;
    //     setTimeout(() => {
    //         this.selectionStart = this.selectionEnd = val.length;
    //     }, 0);
    // });

    window.addEventListener('pageshow', function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 1)) {
            searchInput.value = '';
        }
    });

    // searchInput.focus();

    // Keep cursor at end while typing
    searchInput.addEventListener('input', function () {
        const val = this.value;
        setTimeout(() => { this.selectionStart = this.selectionEnd = val.length; }, 0);
    });

    // Debounce typing auto-submit
    let timer;
    searchInput.addEventListener('keyup', function(e) {
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (this.value.trim() !== '') {
                this.form.submit();
            }
        }, 500);
    });

    // Submit on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.form.submit();
        }
    });
}
</script>

@endsection
