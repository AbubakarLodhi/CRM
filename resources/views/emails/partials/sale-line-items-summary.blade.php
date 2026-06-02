@php
    $sale = $sale->loadMissing(['items.product', 'items.variants.variant']);
    $grandTotalBackground = $grandTotalBackground
        ?? 'linear-gradient(135deg, #0d6efd 0%, #0bb783 62%, #1bc5bd 100%)';
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;margin-bottom:20px;">
    <thead>
        <tr style="background:#f8fafc;">
            <th align="left" style="padding:8px 6px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px;">#</th>
            <th align="left" style="padding:8px 6px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px;">Product</th>
            <th align="right" style="padding:8px 6px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px;">Price</th>
            <th align="center" style="padding:8px 6px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px;">Qty</th>
            <th align="right" style="padding:8px 6px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px;">Subtotal</th>
            <th align="right" style="padding:8px 6px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px;">Discount</th>
            <th align="right" style="padding:8px 6px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px;">Tax</th>
            <th align="right" style="padding:8px 6px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sale->items as $i => $item)
            @php
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountRate = (float) ($item->discount ?? 0);
                $taxRate = (float) ($item->tax ?? 0);

                $discountAmount = $lineTotal * ($discountRate / 100);
                $taxableAmount = $lineTotal - $discountAmount;
                $taxAmount = $taxableAmount * ($taxRate / 100);

                $lineGrandTotal = $taxableAmount + $taxAmount;
            @endphp
            <tr>
                <td style="padding:8px 6px;border-bottom:1px solid #f1f5f9;vertical-align:top;">{{ $i + 1 }}</td>
                <td style="padding:8px 6px;border-bottom:1px solid #f1f5f9;vertical-align:top;">
                    <strong style="color:#0f172a;">{{ $item->product?->name ?? 'Item' }}</strong>
                    @if($item->variants->first()?->variant?->name)
                        <div style="font-size:11px;color:#64748b;margin-top:2px;">
                            {{ $item->variants->first()->variant->name }}
                        </div>
                    @endif
                </td>
                <td align="right" style="padding:8px 6px;border-bottom:1px solid #f1f5f9;white-space:nowrap;">Rs{{ number_format((float) $item->unit_price, 2) }}</td>
                <td align="center" style="padding:8px 6px;border-bottom:1px solid #f1f5f9;">{{ number_format((float) $item->quantity, 2) }}</td>
                <td align="right" style="padding:8px 6px;border-bottom:1px solid #f1f5f9;white-space:nowrap;">Rs{{ number_format($lineTotal, 2) }}</td>
                <td align="right" style="padding:8px 6px;border-bottom:1px solid #f1f5f9;">{{ number_format($discountRate, 2) }}%</td>
                <td align="right" style="padding:8px 6px;border-bottom:1px solid #f1f5f9;">{{ number_format($taxRate, 2) }}%</td>
                <td align="right" style="padding:8px 6px;border-bottom:1px solid #f1f5f9;white-space:nowrap;"><strong>Rs{{ number_format($lineGrandTotal, 2) }}</strong></td>
            </tr>
        @empty
            <tr>
                <td colspan="8" style="padding:12px;color:#64748b;">No line items.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;margin-bottom:8px;">
    <tr>
        <td></td>
        <td width="300">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $grandTotalBackground }};border-radius:8px;">
                <tr>
                    <td style="padding:12px 14px;color:#ffffff;font-size:14px;font-weight:700;">Grand total</td>
                    <td align="right" style="padding:12px 14px;color:#ffffff;font-size:14px;font-weight:700;">Rs {{ number_format((float) $sale->total_amount, 2) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
