<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Statement Export</title>
    @php
        $columnCount = count($headings ?? []);
        $isCompact = $columnCount > 10;
        $isUltraCompact = $columnCount > 14;
    @endphp
    <style>
        @page { margin: 18px 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; margin: 0; }
        .sheet { width: 100%; margin: 0 auto; }
        h2 { margin: 0 0 4px 0; font-size: 18px; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .header-table td { border: none; padding: 0; vertical-align: top; }
        .logo-cell { text-align: right; }
        .logo { max-width: 120px; max-height: 60px; }
        .meta { margin: 0 0 10px 0; font-size: 9.5px; color: #374151; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .statement-table { table-layout: fixed; }
        .statement-table th:nth-child(1),
        .statement-table td:nth-child(1) { width: 10%; }
        .statement-table th:nth-child(2),
        .statement-table td:nth-child(2) { width: 30%; }
        .statement-table th,
        .statement-table td { word-break: break-word; overflow-wrap: anywhere; line-height: 1.25; }
        th, td { border: 1px solid #4b5563; padding: 5px; vertical-align: top; color: #1f2937; }
        th { background: #f3f4f6; text-align: left; font-size: 8.5px; color: #111827; }
        td.num { text-align: right; white-space: nowrap; }
        .summary { margin-top: 12px; width: 42%; margin-left: auto; }
        .summary td { border: 1px solid #4b5563; padding: 6px; color: #1f2937; }
        .summary td:last-child { text-align: right; }
        .bold { font-weight: 700; }

        body.compact .statement-table th:nth-child(1),
        body.compact .statement-table td:nth-child(1) { width: 8%; }
        body.compact .statement-table th:nth-child(2),
        body.compact .statement-table td:nth-child(2) { width: 20%; }
        body.compact th,
        body.compact td { padding: 3px; }
        body.compact th { font-size: 7.4px; }
        body.compact td { font-size: 7.2px; }
        body.compact td.num { white-space: normal; }
        body.compact .summary { width: 48%; }

        body.ultra-compact th,
        body.ultra-compact td { padding: 2px; }
        body.ultra-compact th { font-size: 6.8px; }
        body.ultra-compact td { font-size: 6.6px; }
        body.ultra-compact .summary { width: 52%; }
    </style>
</head>
<body class="{{ $isUltraCompact ? 'ultra-compact' : ($isCompact ? 'compact' : '') }}">
<div class="sheet">
    <table class="header-table">
        <tr>
            <td><h2>Statement</h2></td>
            <td class="logo-cell">
                @if(!empty($merchantLogoDataUri))
                    <img class="logo" src="{{ $merchantLogoDataUri }}" alt="Merchant Logo">
                @endif
            </td>
        </tr>
    </table>
    <p class="meta">
        Customer: {{ $customer->name ?? 'N/A' }} |
        @if(!empty($dateFrom))
            From: {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') }} |
        @endif
        @if(!empty($dateTo))
            To: {{ \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y') }} |
        @endif
        Exported: {{ now()->format('d/m/Y H:i') }}
    </p>

    <table class="statement-table">
        <thead>
        <tr>
            @foreach($headings as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                @foreach($row as $index => $value)
                    @php
                        $heading = strtolower((string) ($headings[$index] ?? ''));
                        $isNumeric = in_array($heading, ['debit', 'credit', 'balance'], true)
                            || str_contains($heading, 'amount')
                            || in_array($heading, ['subtotal', 'discount', 'tax'], true);
                    @endphp
                    <td class="{{ $isNumeric ? 'num' : '' }}">
                        @php
                            $displayValue = $value;
                        if ($isNumeric) {
                            if (is_numeric($value)) {
                                $num = (float) $value;
                                $displayValue = abs($num) < 0.01 ? '' : number_format($num, 2);
                            }
                        }
                        @endphp
                        {{ $displayValue }}
                    </td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ max(count($headings), 1) }}">No records found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    @php
        $closingBalance = (float) ($totals['closing_balance'] ?? 0);
    @endphp
    <table class="summary">
        <tr>
            <td class="bold">Total debits</td>
            <td>{{ number_format((float) ($totals['total_debits'] ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td class="bold">Total credits</td>
            <td>{{ number_format((float) ($totals['total_credits'] ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td class="bold">Closing balance</td>
            <td>{{ number_format($closingBalance, 2) }}</td>
        </tr>
    </table>
</div>
</body>
</html>
