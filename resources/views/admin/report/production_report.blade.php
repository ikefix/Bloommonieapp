@extends('layouts.adminapp')

@section('admincontent')
<div class="container-fluid">

    {{-- TITLE --}}
    <div class="mb-4">
        <h3 class="fw-bold text-primary">Production & Manufacturing Report</h3>
        <p class="text-muted">Overview of all production batches, inputs, outputs, and losses</p>
    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.report.production_report') }}" class="row g-3 mb-4 align-items-end">

        <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Shop</label>
            <select name="shop_id" class="form-select">
                <option value="">All Shops</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ $shopId == $shop->id ? 'selected' : '' }}>
                        {{ $shop->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-grid">
            <button class="btn btn-primary">Apply Filter</button>
        </div>

        <div class="col-md-3 d-grid">
            <a href="{{ route('admin.report.pdf.production_report.pdf', request()->query()) }}" class="btn btn-dark">
                <i class="bi bi-download"></i> Download PDF
            </a>
        </div>

    </form>

    {{-- SUMMARY CARDS --}}
    <div class="row g-3 mb-4">

        <div class="col-md-2">
            <div class="card border-0 shadow-sm p-3 text-center bg-light">
                <h6 class="text-muted">Total Batches</h6>
                <h4 class="fw-bold text-primary">{{ $totalProductions }}</h4>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card border-0 shadow-sm p-3 text-center bg-light">
                <h6 class="text-muted">Completed</h6>
                <h4 class="fw-bold text-success">{{ $completedCount }}</h4>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card border-0 shadow-sm p-3 text-center bg-light">
                <h6 class="text-muted">In Progress</h6>
                <h4 class="fw-bold text-warning">{{ $inProgressCount }}</h4>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card border-0 shadow-sm p-3 text-center bg-light">
                <h6 class="text-muted">Pending</h6>
                <h4 class="fw-bold text-secondary">{{ $pendingCount }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center bg-light">
                <h6 class="text-muted">Net Value (Output - Input - Loss)</h6>
                <h4 class="fw-bold {{ $netValue >= 0 ? 'text-success' : 'text-danger' }}">
                    ₦{{ number_format($netValue, 2) }}
                </h4>
            </div>
        </div>

    </div>

    {{-- COST SUMMARY --}}
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center" style="background:#fff3cd;">
                <h6 class="text-muted">Total Input Cost</h6>
                <h4 class="fw-bold text-warning">₦{{ number_format($totalInputCost, 2) }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center" style="background:#d1f7e0;">
                <h6 class="text-muted">Total Output Value</h6>
                <h4 class="fw-bold text-success">₦{{ number_format($totalOutputValue, 2) }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 text-center" style="background:#f8d7da;">
                <h6 class="text-muted">Total Loss Value</h6>
                <h4 class="fw-bold text-danger">₦{{ number_format($totalLossValue, 2) }}</h4>
            </div>
        </div>

    </div>

    {{-- PRODUCTIONS BY TYPE + TOP OUTPUTS side by side --}}
    <div class="row g-3 mb-4">

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><strong>Productions by Type</strong></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Type</th><th>Count</th></tr>
                        </thead>
                        <tbody>
                            @forelse($byType as $type => $count)
                                <tr><td>{{ $type }}</td><td>{{ $count }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white"><strong>Top Output Products (by Qty)</strong></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Item</th><th>Total Qty</th></tr>
                        </thead>
                        <tbody>
                            @forelse($outputTotals as $name => $qty)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $name }}</td>
                                    <td>{{ number_format($qty, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-6">
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="BATCH-001"
                            value="{{ request('search') }}">
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">
                            Search
                        </button>
                    </div>

                    <div class="col-md-2">
                        <a href="{{ url()->current() }}"
                        class="btn btn-secondary w-100">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- PRODUCTION BATCH DETAILS --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white"><strong>Production Batch Details</strong></div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Batch No</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Shop</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th>Inputs</th>
                        <th>Outputs</th>
                        <th>Losses</th>
                        <th>Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productions as $p)
                        @php
                            $inCost  = collect($p->parsed_inputs)->sum(fn($i)  => (float)($i['price'] ?? 0));
                            $outVal  = collect($p->parsed_outputs)->sum(fn($o) => (float)($o['price'] ?? 0));
                            $lossVal = collect($p->parsed_losses)->sum(fn($l)  => (float)($l['price'] ?? 0));
                            $net     = $outVal - $inCost - $lossVal;
                        @endphp
                        <tr>
                            <td><strong>{{ $p->batch_no }}</strong></td>
                            <td>{{ $p->title }}</td>
                            <td>{{ $p->productionType->name ?? '-' }}</td>
                            <td>{{ $p->shop->name ?? '-' }}</td>
                            <td>{{ $p->start_date }}</td>
                            <td>{{ $p->end_date ?? '-' }}</td>
                            <td>
                                    {{ ucfirst($p->status) }}
                                
                            </td>
                            <td class="text-warning">₦{{ number_format($inCost, 2) }}</td>
                            <td class="text-success">₦{{ number_format($outVal, 2) }}</td>
                            <td class="text-danger">₦{{ number_format($lossVal, 2) }}</td>
                            <td class="{{ $net >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                ₦{{ number_format($net, 2) }}
                            </td>
                        </tr>

                        {{-- Expandable item detail rows --}}
                        @if(count($p->parsed_inputs) || count($p->parsed_outputs) || count($p->parsed_losses))
                        <tr class="table-light">
                            <td colspan="11" class="p-2">
                                <div class="row g-2 small">

                                    @if(count($p->parsed_inputs))
                                    <div class="col-md-4">
                                        <strong class="text-primary">Inputs:</strong>
                                        <ul class="mb-0 ps-3">
                                            @foreach($p->parsed_inputs as $i)
                                                <li>
                                                    {{ \App\Models\Product::find($i['item_id'])?->name ?? 'Unknown Product' }}
                                                    — {{ $i['quantity'] ?? 0 }} {{ $i['unit'] ?? '' }}
                                                    @ ₦{{ number_format((float)($i['price'] ?? 0), 2) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    @endif

                                    @if(count($p->parsed_outputs))
                                    <div class="col-md-4">
                                        <strong class="text-success">Outputs:</strong>
                                        <ul class="mb-0 ps-3">
                                            @foreach($p->parsed_outputs as $o)
                                                <li>
                                                    {{ $o['item_name'] ?? '?' }}
                                                    — {{ $o['quantity'] ?? 0 }} {{ $o['unit'] ?? '' }}
                                                    @ ₦{{ number_format($o['price'] ?? 0, 2) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    @endif

                                    @if(count($p->parsed_losses))
                                    <div class="col-md-4">
                                        <strong class="text-danger">Losses:</strong>
                                        <ul class="mb-0 ps-3">
                                            @foreach($p->parsed_losses as $l)
                                                <li>
                                                    {{ $l['item_name'] ?? '?' }}
                                                    — {{ $l['quantity'] ?? 0 }} {{ $l['unit'] ?? '' }}
                                                    @ ₦{{ number_format($l['price'] ?? 0, 2) }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    @endif

                                </div>
                            </td>
                        </tr>
                        @endif

                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-3">
                                No productions found for selected filters
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- CHART --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white"><strong>Productions Over Time</strong></div>
        <div class="card-body">
            <canvas id="productionChart" style="height:300px;"></canvas>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = @json($chartLabels);
const counts = @json($chartCounts);
    new Chart(document.getElementById('productionChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Batches Started',
                data: counts,
                backgroundColor: 'rgba(13, 110, 253, 0.6)',
                borderColor: '#0d6efd',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endsection