<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Production Report</title>

    <style>
        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table{
            width:100%;
            border-collapse: collapse;
            margin-top:10px;
        }

        table, th, td{
            border:1px solid #000;
        }

        th, td{
            padding:6px;
            text-align:left;
        }

        .title{
            text-align:center;
            margin-bottom:20px;
        }

        .summary{
            margin-bottom:20px;
        }
    </style>
</head>
<body>

    <div class="title">
        <h2>Production & Manufacturing Report</h2>
    </div>

    <div class="summary">
        <p><strong>Total Batches:</strong> {{ $totalProductions ?? 0 }}</p>
        <p><strong>Completed:</strong> {{ $completedCount ?? 0 }}</p>
        <p><strong>In Progress:</strong> {{ $inProgressCount ?? 0 }}</p>
        <p><strong>Pending:</strong> {{ $pendingCount ?? 0 }}</p>

        <p><strong>Total Input Cost:</strong>
            ₦{{ number_format($totalInputCost ?? 0,2) }}
        </p>

        <p><strong>Total Output Value:</strong>
            ₦{{ number_format($totalOutputValue ?? 0,2) }}
        </p>

        <p><strong>Total Loss Value:</strong>
            ₦{{ number_format($totalLossValue ?? 0,2) }}
        </p>

        <p><strong>Net Value:</strong>
            ₦{{ number_format($netValue ?? 0,2) }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Batch No</th>
                <th>Title</th>
                <th>Type</th>
                <th>Shop</th>
                <th>Status</th>
                <th>Input Cost</th>
                <th>Output Value</th>
                <th>Loss Value</th>
                <th>Net</th>
            </tr>
        </thead>

        <tbody>

            @forelse($productions as $p)

                @php
                    $inCost = collect($p->parsed_inputs ?? [])
                        ->sum(fn($i)=>(float)($i['price'] ?? 0));

                    $outVal = collect($p->parsed_outputs ?? [])
                        ->sum(fn($i)=>(float)($i['price'] ?? 0));

                    $lossVal = collect($p->parsed_losses ?? [])
                        ->sum(fn($i)=>(float)($i['price'] ?? 0));

                    $net = $outVal - $inCost - $lossVal;
                @endphp

                <tr>
                    <td>{{ $p->batch_no }}</td>
                    <td>{{ $p->title }}</td>
                    <td>{{ $p->productionType->name ?? '-' }}</td>
                    <td>{{ $p->shop->name ?? '-' }}</td>
                    <td>{{ ucfirst($p->status) }}</td>
                    <td>₦{{ number_format($inCost,2) }}</td>
                    <td>₦{{ number_format($outVal,2) }}</td>
                    <td>₦{{ number_format($lossVal,2) }}</td>
                    <td>₦{{ number_format($net,2) }}</td>
                </tr>

            @empty

                <tr>
                    <td colspan="9">
                        No production records found
                    </td>
                </tr>

            @endforelse

        </tbody>
    </table>

</body>
</html>