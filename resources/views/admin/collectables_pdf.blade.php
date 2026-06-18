<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <style>
        body{
            font-family: DejaVu Sans;
            font-size:12px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-bottom:20px;
        }

        th,td{
            border:1px solid #ddd;
            padding:6px;
        }

        th{
            background:#f2f2f2;
        }

        h2,h3{
            margin-bottom:10px;
        }
    </style>
</head>
<body>

<h2>Collectables Report</h2>

<p>
    From:
    {{ $startDate->format('d M Y') }}
    -
    To:
    {{ $endDate->format('d M Y') }}
</p>

@foreach($invoices as $cashierId => $cashierInvoices)

    @php
        $cashier = $cashierInvoices->first()->cashier;
        $logs = $paymentLogs[$cashierId] ?? collect();
    @endphp

    <h3>
        {{ $cashier?->name ?? 'Unknown Cashier' }}
    </h3>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Balance</th>
            </tr>
        </thead>

        <tbody>
            @foreach($cashierInvoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ number_format($invoice->total,2) }}</td>
                    <td>{{ number_format($invoice->amount_paid,2) }}</td>
                    <td>{{ number_format($invoice->balance,2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Added</th>
                <th>Total Paid</th>
                <th>Balance</th>
                <th>Updated By</th>
            </tr>
        </thead>

        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->invoice_no }}</td>
                    <td>{{ number_format($log->amount_added,2) }}</td>
                    <td>{{ number_format($log->total_paid,2) }}</td>
                    <td>{{ number_format($log->balance,2) }}</td>
                    <td>{{ $log->updated_by }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endforeach

</body>
</html>