@php
    // 280000.00 => "280,000", 2837489.97 => "2,837,489.97"
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Debtors Log</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 3px; }
        h2 { font-size: 13px; margin: 0 0 3px; }
        .muted { color: #6b7280; }
        .meta { margin-bottom: 12px; line-height: 1.5; }

        table { width: 100%; border-collapse: collapse; }
        .summary td { border: 1px solid #e5e7eb; padding: 8px 10px; width: 33.33%; }
        .summary .label { font-size: 8px; color: #6b7280; text-transform: uppercase; }
        .summary .value { font-size: 14px; font-weight: bold; }

        .product { margin-top: 20px; }
        .totals-line { margin-bottom: 6px; }

        .grid th { background: #f3f4f6; font-size: 8px; text-transform: uppercase; color: #4b5563;
                   text-align: left; padding: 6px 5px; border-bottom: 1px solid #d1d5db; }
        .grid td { padding: 6px 5px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .grid tr { page-break-inside: avoid; }
        .grid tfoot td { font-weight: bold; background: #f3f4f6; border-top: 2px solid #d1d5db; border-bottom: none; }
        .r { text-align: right; }
        .phone { color: #6b7280; font-size: 8.5px; }
        .owe { color: #b91c1c; font-weight: bold; }
        .empty { padding: 30px; text-align: center; color: #6b7280; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <h1>Debtors Log</h1>
    <div class="meta muted">
        Shop: {{ $shopName }} &nbsp;|&nbsp; Cashier: {{ $cashierName }}<br>
        Generated: {{ $generatedAt }}
    </div>

    <table class="summary">
        <tr>
            <td><div class="label">Products owing</div><div class="value">{{ $n($summary['products']) }}</div></td>
            <td><div class="label">Customers owing</div><div class="value">{{ $n($summary['customers']) }}</div></td>
            <td><div class="label">Total balance</div><div class="value">₦{{ $n($summary['balance']) }}</div></td>
        </tr>
    </table>

    @forelse ($products as $p)
        <div class="product">
            <h2>{{ $p['name'] }}</h2>
            <div class="totals-line muted">
                Total owed: <strong>{{ $n($p['total_quantity']) }}</strong>
                &nbsp;·&nbsp; Customers: <strong>{{ $n($p['total_customers']) }}</strong>
                &nbsp;·&nbsp; Balance: <strong>₦{{ $n($p['total_balance']) }}</strong>
            </div>

            <table class="grid">
                <thead>
                    <tr>
                        <th style="width: 4%">#</th>
                        <th>Customer</th>
                        <th class="r" style="width: 9%">Qty</th>
                        <th class="r" style="width: 17%">Total</th>
                        <th class="r" style="width: 17%">Paid</th>
                        <th class="r" style="width: 19%">Balance owing</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($p['customers'] as $i => $c)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                {{ $c['name'] ?: 'Unnamed customer' }}
                                <div class="phone">{{ $c['phone'] ?: '—' }}</div>
                            </td>
                            <td class="r">{{ $n($c['quantity']) }}</td>
                            <td class="r">₦{{ $n($c['total']) }}</td>
                            <td class="r">₦{{ $n($c['paid']) }}</td>
                            <td class="r owe">₦{{ $n($c['balance']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td>{{ $n($p['total_customers']) }} customer{{ $p['total_customers'] === 1 ? '' : 's' }}</td>
                        <td class="r">{{ $n($p['total_quantity']) }}</td>
                        <td class="r">₦{{ $n($p['total_amount']) }}</td>
                        <td class="r">₦{{ $n($p['total_paid']) }}</td>
                        <td class="r owe">₦{{ $n($p['total_balance']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @empty
        <div class="product empty">No one is owing right now.</div>
    @endforelse
</body>
</html>