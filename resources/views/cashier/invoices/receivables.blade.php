@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <!-- Total Receivables -->
    <!-- <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Receivables</h6>
                    <h2 class="fw-bold text-danger">
                        ₦{{ number_format($totalReceivable, 2) }}
                    </h2>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Table -->
    <div class="card shadow-sm border-0">

        <!-- Search -->
        <div class="card-header bg-white">
            <form method="GET">
                <div class="row">
                    <div class="col-md-10">
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Search customer name or phone"
                               value="{{ request('search') }}">
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">

            <table class="table table-hover mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Shop</th>
                        <th>Goods</th>
                        <th>Total Invoice</th>
                        <th>Amount Paid</th>
                        <th style="color:red;">Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($customers as $customer)

                    @php
                        $invoices = $invoicesByCustomer[$customer->customer_id] ?? collect();

                        // GET SHOP (latest invoice shop)
                        $latestInvoice = $invoices->sortByDesc('id')->first();

                        // MERGE ALL GOODS
                        $allGoods = [];

                        foreach ($invoices as $inv) {

                            $goods = is_string($inv->goods)
                                ? json_decode($inv->goods, true)
                                : $inv->goods;

                            if (is_array($goods)) {
                                foreach ($goods as $item) {
                                    $name = $item['name'] ?? 'Item';
                                    $qty = $item['qty'] ?? 1;

                                    $allGoods[$name] = ($allGoods[$name] ?? 0) + $qty;
                                }
                            }
                        }
                    @endphp

                    <tr>

                        <!-- Customer -->
                        <td>
                            <strong>{{ $customer->customer->name ?? '' }}</strong><br>
                            <small class="text-muted">{{ $customer->customer->phone ?? '' }}</small>
                        </td>

                        <!-- Shop -->
                        <td>
                            {{ $latestInvoice->shop->name ?? 'N/A' }}
                        </td>

                        <!-- Goods -->
                        <td>
                            @if(!empty($allGoods))
                                <ul class="mb-0 ps-3">
                                    @foreach($allGoods as $name => $qty)
                                        <li>{{ $name }} (x{{ $qty }})</li>
                                    @endforeach
                                </ul>
                            @else
                                -
                            @endif
                        </td>

                        <!-- Total -->
                        <td>₦{{ number_format($customer->total_invoice, 2) }}</td>

                        <!-- Paid -->
                        <td>₦{{ number_format($customer->total_paid, 2) }}</td>

                        <!-- Balance -->
                        <td>
                            @if($customer->total_owing > 0)
                                <!-- <span class="badge bg-danger"> -->
                                    ₦{{ number_format($customer->total_owing, 2) }}
                                <!-- </span> -->
                            @else
                                Not owing
                            @endif
                        </td>

                        <!-- Action -->
                        <td>
                            <a href="{{ route('cashier.customers.index', $customer->customer_id) }}"
                               class="btn btn-sm btn-primary">
                                View Customer
                            </a>
                        </td>

                    </tr>

                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            No records found for this Customer.
                        </td>
                    </tr>
                @endforelse

                </tbody>

            </table>

        </div>

        <!-- Pagination -->
        <div class="card-footer bg-white">
            {{ $customers->withQueryString()->links() }}
        </div>

    </div>

</div>

@endsection