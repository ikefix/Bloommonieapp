@extends('layouts.adminapp')

@section('admincontent')

<style>
    .page-title {
        font-weight: 700;
        margin-bottom: 15px;
    }

    .cashier-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        margin-bottom: 25px;
        overflow: hidden;
    }

    .cashier-header {
        background: linear-gradient(135deg, #1f2937, #374151);
        color: #fff;
        padding: 12px 16px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .badge-soft {
        background: rgba(255,255,255,0.15);
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
    }

    .section-title {
        font-weight: 600;
        margin: 15px 0 10px;
        font-size: 14px;
        color: #374151;
    }

    table {
        font-size: 13px;
    }

    .table thead {
        background: #f3f4f6;
    }

    .table td, .table th {
        vertical-align: middle;
    }

    .money {
        font-weight: 600;
        color: #111827;
    }

    .positive {
        color: #16a34a;
        font-weight: 600;
    }

    .negative {
        color: #dc2626;
        font-weight: 600;
    }

    .filter-box {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
    }

    .filter-box input {
        border-radius: 8px;
        padding: 5px 10px;
        border: 1px solid #ddd;
    }
</style>

<div class="container">
    @php
        $grandSales = $invoices->flatten()->sum('total');
        $grandPaid = $invoices->flatten()->sum('amount_paid');
        $grandBalance = $invoices->flatten()->sum('balance');
        $cashierCount = $invoices->count();
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="page-title mb-0">
                Collectables Dashboard
            </h3>

            <small class="text-muted">
                {{ $startDate->format('d M Y') }}
                -
                {{ $endDate->format('d M Y') }}
            </small>
        </div>
    </div>

    <div class="row mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <small class="text-muted">Active Cashiers</small>
                    <h3>{{ $cashierCount }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <small class="text-muted">Total Sales</small>
                    <h3>₦{{ number_format($grandSales, 2) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <small class="text-muted">Total Collected</small>
                    <h3 class="text-success">
                        ₦{{ number_format($grandPaid, 2) }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <small class="text-muted">Outstanding</small>
                    <h3 class="text-danger">
                        ₦{{ number_format($grandBalance, 2) }}
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <form method="GET" class="filter-box flex-wrap">

        <div>
            <small class="text-muted d-block">From</small>
            <input
                type="date"
                name="start_date"
                value="{{ $startDate->format('Y-m-d') }}"
                class="form-control">
        </div>

        <div>
            <small class="text-muted d-block">To</small>
            <input
                type="date"
                name="end_date"
                value="{{ $endDate->format('Y-m-d') }}"
                class="form-control">
        </div>

        <div>
            <small class="text-muted d-block">Shop</small>

            <select name="shop_id" class="form-control">
                <option value="all">All Shops</option>

                @foreach($shops as $shop)
                    <option
                        value="{{ $shop->id }}"
                        {{ $shopId == $shop->id ? 'selected' : '' }}>
                        {{ $shop->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <small class="text-muted d-block">Cashier</small>

            <select name="cashier_id" class="form-control">
                <option value="all">All Cashiers</option>

                @foreach($cashiers as $cashier)
                    <option
                        value="{{ $cashier->id }}"
                        {{ $cashierId == $cashier->id ? 'selected' : '' }}>
                        {{ $cashier->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mt-4 d-flex gap-2">

            <button type="submit" class="btn btn-primary">
                <i class="bx bx-filter"></i> Filter
            </button>

            <a href="{{ route('admin.collectables.pdf', [
                    'start_date' => request('start_date'),
                    'end_date' => request('end_date'),
                    'shop_id' => request('shop_id'),
                    'cashier_id' => request('cashier_id')
                ]) }}"
            class="btn btn-danger">
                <i class="bx bxs-file-pdf"></i> Download PDF
            </a>

        </div>

    </form>

    @if($invoices->isEmpty())
        <div class="alert alert-warning">
            No cashier activity found for this date.
        </div>
    @else

        @foreach($invoices as $cashierId => $cashierInvoices)

            @php
                $cashier = $cashierInvoices->first()->cashier;
                $logs = $paymentLogs[$cashierId] ?? collect();

                $totalSales = $cashierInvoices->sum('total');
                $totalPaid = $cashierInvoices->sum('amount_paid');
                $totalBalance = $cashierInvoices->sum('balance');
            @endphp

            <div class="card cashier-card">

                {{-- HEADER --}}
                <div class="cashier-header">
                    <div>
                        {{ $cashier?->name ?? 'Unknown Cashier' }}
                    </div>

                    <div class="badge-soft">
                        ₦{{ number_format($totalPaid, 2) }} Collected
                    </div>
                </div>

                <div class="card-body">

                    {{-- SUMMARY --}}
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <small>Total Sales</small>
                            <div class="money">₦{{ number_format($totalSales, 2) }}</div>
                        </div>

                        <div class="col-md-4">
                            <small>Total Paid</small>
                            <div class="positive">₦{{ number_format($totalPaid, 2) }}</div>
                        </div>

                        <div class="col-md-4">
                            <small>Balance</small>
                            <div class="negative">₦{{ number_format($totalBalance, 2) }}</div>
                        </div>
                    </div>

                    {{-- INVOICES --}}
                    <div class="section-title">Invoices Created Today</div>

                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($cashierInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>₦{{ number_format($invoice->total, 2) }}</td>
                                        <td class="positive">₦{{ number_format($invoice->amount_paid, 2) }}</td>
                                        <td class="negative">₦{{ number_format($invoice->balance, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $invoice->payment_status == 'paid' ? 'success' : 'warning' }}">
                                                {{ $invoice->payment_status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- ACTIVITY LOG --}}
                    <div class="section-title">Payment Update History (Permanent Audit Log)</div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover">

                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Added</th>
                                    <th>Total Paid</th>
                                    <th>Balance</th>
                                    <th>Updated By</th>
                                    <th>Time</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($logs as $log)

                                            <tr>
                                                <td>#{{ $log->invoice_no }}</td>

                                                <td class="positive">
                                                    ₦{{ number_format($log->amount_added, 2) }}
                                                </td>

                                                <td>
                                                    ₦{{ number_format($log->total_paid, 2) }}
                                                </td>

                                                <td class="negative">
                                                    ₦{{ number_format($log->balance, 2) }}
                                                </td>

                                                <td>
                                                    {{ $log->updated_by }}
                                                </td>

                                                <td>
                                                    {{ \Carbon\Carbon::parse($log->payment_updated_at)->format('d M Y h:i A') }}
                                                </td>
                                            </tr>

                                        @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No updates recorded
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>

                </div>
            </div>

        @endforeach

    @endif

</div>

@endsection