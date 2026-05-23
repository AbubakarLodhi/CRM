@php
    $purchase = $purchase->loadMissing(['items.product', 'vendor', 'merchant', 'payments']);
    $paidAmount = (float) ($purchase->paid_amount ?? 0);
    $remainingAmount = (float) ($purchase->due_amount ?? 0);
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;margin-top:12px;">
    <tr>
        <td colspan="2" style="padding:8px 0;font-weight:bold;">Purchase {{ $purchase->purchase_no }}</td>
    </tr>
    <tr>
        <td style="padding:4px 0;color:#64748b;">Date</td>
        <td style="padding:4px 0;">{{ $purchase->purchase_date?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td style="padding:4px 0;color:#64748b;">Vendor</td>
        <td style="padding:4px 0;">{{ $purchase->vendor?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td style="padding:4px 0;color:#64748b;">Total</td>
        <td style="padding:4px 0;">PKR {{ number_format((float) $purchase->total_amount, 2) }}</td>
    </tr>
    <tr>
        <td style="padding:4px 0;color:#64748b;">Paid</td>
        <td style="padding:4px 0;">PKR {{ number_format($paidAmount, 2) }}</td>
    </tr>
    <tr>
        <td style="padding:4px 0;color:#64748b;">Due</td>
        <td style="padding:4px 0;">PKR {{ number_format($remainingAmount, 2) }}</td>
    </tr>
</table>
