<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Sales Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h2 { margin: 0 0 6px 0; font-size: 18px; }
        .meta { margin: 0 0 12px 0; font-size: 10px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-size: 10px; }
        td.num { text-align: right; white-space: nowrap; }
        .summary { margin-top: 12px; width: 40%; margin-left: auto; }
        .summary td { border: 1px solid #d1d5db; padding: 6px; }
        .summary td:last-child { text-align: right; }
        .bold { font-weight: 700; }
    </style>
</head>
<body>
    <h2>Customer Sales Report</h2>
    <p class="meta">
        Customer: {{ $customer->name ?? 'N/A' }} |
        Exported: {{ now()->format('d/m/Y H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Sale No</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Merchant</th>
                <th>Branch</th>
                <th>Payment</th>
                <th>Paid Amount</th>
                <th>Due Amount</th>
                <th>Items Count</th>
                <th>Subtotal</th>
                <th>Discount</th>
                <th>Tax</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                @php
                    $branches = $sale->items->pluck('branch.name')->filter()->unique()->values();
                    $branchText = $branches->count() > 2
                        ? $branches->take(2)->implode(', ') . ' +' . ($branches->count() - 2) . ' more'
                        : $branches->implode(', ');

                    $returnedSubtotal = (float) $sale->returns->sum('subtotal');
                    $returnedDiscount = (float) $sale->returns->sum('total_discount');
                    $returnedTax = (float) $sale->returns->sum('total_tax');
                    $returnedTotal = (float) $sale->returns->sum('total_amount');

                    $discount = (float) $sale->items->sum(function ($item) {
                        $lineTotal = (float) ($item->line_total ?? 0);
                        $discountRate = (float) ($item->discount ?? 0);
                        return $lineTotal * ($discountRate / 100);
                    }) + $returnedDiscount;

                    $tax = (float) $sale->items->sum(function ($item) {
                        $lineTotal = (float) ($item->line_total ?? 0);
                        $discountRate = (float) ($item->discount ?? 0);
                        $taxRate = (float) ($item->tax ?? 0);
                        $discountAmount = $lineTotal * ($discountRate / 100);
                        $taxableAmount = $lineTotal - $discountAmount;
                        return $taxableAmount * ($taxRate / 100);
                    }) + $returnedTax;
                @endphp
                <tr>
                    <td>{{ $sale->sale_no }}</td>
                    <td>{{ optional($sale->sale_date)->format('d/m/Y') }}</td>
                    <td>{{ $sale->customer?->name }}</td>
                    <td>{{ $sale->merchant?->name }}</td>
                    <td>{{ $branchText ?: '-' }}</td>
                    <td>{{ ucfirst((string) ($sale->payment_type ?? '')) }}</td>
                    <td class="num">{{ number_format((float) ($sale->paid_amount ?? 0), 2) }}</td>
                    <td class="num">{{ number_format((float) ($sale->due_amount ?? 0), 2) }}</td>
                    <td class="num">{{ (int) $sale->items_count }}</td>
                    <td class="num">{{ number_format((float) $sale->subtotal + $returnedSubtotal, 2) }}</td>
                    <td class="num">{{ number_format($discount, 2) }}</td>
                    <td class="num">{{ number_format($tax, 2) }}</td>
                    <td class="num">{{ number_format((float) $sale->total_amount + $returnedTotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td class="bold">Total Amount</td>
            <td>{{ number_format((float) ($totals['total_amount'] ?? $totals['total'] ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td class="bold">Amount Paid</td>
            <td>{{ number_format((float) ($totals['amount_paid'] ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td class="bold">Amount Pending</td>
            <td>{{ number_format((float) ($totals['amount_pending'] ?? 0), 2) }}</td>
        </tr>
    </table>
</body>
</html>
