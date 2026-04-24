<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cash Flow Statement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
        }
        .header {
            margin-bottom: 20px;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 12px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .meta {
            margin-bottom: 3px;
            color: #374151;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
        }
        .right {
            text-align: right;
        }
        .totals {
            margin-top: 18px;
            width: 40%;
            margin-left: auto;
        }
        .muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        @if (! empty($merchantLogoDataUri))
            <img class="logo" src="{{ $merchantLogoDataUri }}" alt="Merchant Logo">
        @endif

        <div class="title">Cash Flow Statement</div>
        <div class="meta"><strong>{{ $partyLabel }}:</strong> {{ $party->name }}</div>
        <div class="meta"><strong>Phone:</strong> {{ $party->phone ?: '-' }}</div>
        <div class="meta"><strong>Email:</strong> {{ $party->email ?: '-' }}</div>
        <div class="meta"><strong>Generated:</strong> {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Account Type</th>
                <th>Direction</th>
                <th>Amount</th>
                <th>Settled</th>
                <th>Remaining</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cashFlows as $cashFlow)
                <tr>
                    <td>{{ optional($cashFlow->flow_date)->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ \App\Models\CashFlow::flowTypeLabel($cashFlow->flow_type) }}</td>
                    <td>{{ $cashFlow->direction === 'in' ? 'In' : 'Out' }}</td>
                    <td class="right">{{ number_format((float) $cashFlow->amount, 2) }}</td>
                    <td class="right">{{ number_format((float) $cashFlow->settlements->sum('amount'), 2) }}</td>
                    <td class="right">{{ number_format(max(0, (float) $cashFlow->amount - (float) $cashFlow->settlements->sum('amount')), 2) }}</td>
                    <td>{{ $cashFlow->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted">No cash flow records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tbody>
            <tr>
                <th>Total</th>
                <td class="right">{{ number_format((float) $totals['total_amount'], 2) }}</td>
            </tr>
            <tr>
                <th>Settled</th>
                <td class="right">{{ number_format((float) $totals['settled_amount'], 2) }}</td>
            </tr>
            <tr>
                <th>Remaining</th>
                <td class="right">{{ number_format((float) $totals['remaining_amount'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
