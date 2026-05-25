@extends('layouts.adminapp')

@section('admincontent')
<div class="container-fluid py-4">

    {{-- Dashboard Cards --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total People Owing</h5>
                        <h3 class="card-text">{{ $owingInvoices->count() }}</h3>
                    </div>
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-danger shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Balance Owed</h5>
                        <h3 class="card-text">&#8358;{{ number_format($owingInvoices->sum('balance'), 2) }}</h3>
                    </div>
                    <i class="fas fa-money-bill-wave fa-2x"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Invoices</h5>
                        <h3 class="card-text">{{ $totalInvoices }}</h3>
                    </div>
                    <i class="fas fa-file-invoice fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Owing Invoices Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Invoices</h4>
                    <a href="{{ route('admin.invoices.create') }}" class="btn btn-sm btn-primary">New Invoice</a>
                </div>
                <div class="card-body">
                    @if($invoices->count())
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Shop</th>
                                    <th>Goods</th>
                                    <th>Total</th>
                                    <th>Quantity</th>
                                    <th>Amount Paid</th>
                                    <th>Balance</th>
                                    <th>Invoice Date</th>
                                    <th>payment_type</th>
                                    <th>payment_status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $invoice)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $invoice->customer->name }}</td>
                                    <td>{{ $invoice->shop->name }}</td>
                                    <td>
                                        {{ $invoice->product_name }}
                                    </td>



                                    <td>&#8358;{{ number_format($invoice->total, 2) }}</td>
                                    <td>{{ $invoice->quantity }}</td>
                                    <td>&#8358;{{ number_format($invoice->amount_paid, 2) }}</td>
                                    <td class="text-danger">&#8358;{{ number_format($invoice->balance, 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M, Y') }}</td>
                                    <td>{{ $invoice->payment_type }}</td>
                                    <td>{{ $invoice->payment_status }}</td>
                                    <td>
                                        <a href="{{ route('admin.invoices.edit-payment', $invoice->id) }}" class="btn btn-sm btn-info">Edit</a>
                                        <form action="{{ route('admin.invoices.mark-paid', $invoice->id) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            <input type="hidden" name="payment_type" value="full">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                Mark Paid
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.invoices.preview', $invoice->id) }}" class="btn btn-sm btn-secondary">
                                            Preview
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                        <div class="alert alert-secondary">No owing invoices found.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
