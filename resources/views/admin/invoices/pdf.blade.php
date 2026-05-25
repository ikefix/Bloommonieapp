<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .container {
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header img {
            max-height: 70px;
            margin-bottom: 8px;
        }

        .header h2 {
            margin: 0;
            letter-spacing: 1px;
        }

        .header small {
            color: #666;
        }

        .section {
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .section-title {
            background: #222;
            color: #fff;
            padding: 10px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
        }

        .section-body {
            padding: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        th {
            background: #f2f2f2;
            text-transform: uppercase;
            font-size: 11px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-table td {
            border: none;
            padding: 6px 0;
        }

        .alert-success {
            background: #e6f4ea;
            border: 1px solid #a3d9b1;
            padding: 10px;
            font-weight: bold;
        }

        .alert-danger {
            background: #fdecea;
            border: 1px solid #f5b5b0;
            padding: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <img src="{{ public_path('logobloomp.png') }}">
        <h2>INVOICE</h2>
        <small>Invoice No: {{ $invoice->invoice_number }}</small>>
        <h2>Sales Man</h2>
        <small>{{ $invoice->user->name }}</small>
    </div>

    <!-- CUSTOMER DETAILS -->
    <div class="section">
        <div class="section-title">Customer Details</div>
        <div class="section-body">
            <table>
                <tr>
                    <td>
                        <strong>Name:</strong> {{ $invoice->customer->name }}<br>
                        <strong>Email:</strong> {{ $invoice->customer->email }}<br>
                        <strong>Phone:</strong> {{ $invoice->customer->phone }}
                    </td>
                    <td>
                        @if($invoice->customer->company)
                            <strong>Company:</strong> {{ $invoice->customer->company }}<br>
                        @endif

                        @if($invoice->customer->address)
                            <strong>Address:</strong> {{ $invoice->customer->address }}
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- PURCHASED GOODS -->
    <div class="section">
        <div class="section-title">Purchased Goods</div>
        <div class="section-body" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-right">Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $goods = is_array($invoice->goods)
                            ? $invoice->goods
                            : json_decode($invoice->goods, true);
                    @endphp

                    @foreach($goods as $item)
                        @php
                            $product = \App\Models\Product::find($item['product_id'] ?? null);
                        @endphp

                        <tr>
                            <td>{{ $product?->name ?? 'Unknown Product' }}</td>

                            <td class="text-center">
                                {{ $item['quantity'] ?? 0 }}
                            </td>

                            <td class="text-right">
                                ₦{{ number_format($item['total_price'] ?? 0, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAYMENT SUMMARY -->
    <div class="section">
        <div class="section-title">Payment Summary</div>
        <div class="section-body">
            <table class="summary-table">
                <tr>
                    <td><strong>Total Amount:</strong></td>
                    <td class="text-right">₦{{ number_format($invoice->total, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Amount Paid:</strong></td>
                    <td class="text-right">₦{{ number_format($invoice->amount_paid, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Payment Type:</strong></td>
                    <td class="text-right">{{ ucfirst($invoice->payment_type) }}</td>
                </tr>
            </table>

            <br>

            @if($invoice->payment_status === 'owing')
                <div class="alert-danger">
                    Outstanding Balance: ₦{{ number_format($invoice->balance, 2) }}
                </div>
            @else
                <div class="alert-success">
                    Status: Fully Paid
                </div>
            @endif
        </div>
    </div>

</div>

</body>
</html>
