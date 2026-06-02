@php
    $sale = $sale->loadMissing(['items.product', 'items.variants.variant']);
@endphp
@forelse($sale->items as $i => $item)
@php
    $lineTotal = (float) ($item->line_total ?? 0);
    $discountRate = (float) ($item->discount ?? 0);
    $taxRate = (float) ($item->tax ?? 0);
    $discountAmount = $lineTotal * ($discountRate / 100);
    $taxableAmount = $lineTotal - $discountAmount;
    $taxAmount = $taxableAmount * ($taxRate / 100);
    $lineGrandTotal = $taxableAmount + $taxAmount;
    $variantName = $item->variants->first()?->variant?->name;
@endphp
*{{ $i + 1 }}. {{ $item->product?->name ?? 'Item' }}@if($variantName) ({{ $variantName }})@endif*
Unit: Rs {{ number_format((float) $item->unit_price, 2) }} | Qty: {{ number_format((float) $item->quantity, 2) }} | Discount: {{ number_format($discountRate, 2) }}% | Tax: {{ number_format($taxRate, 2) }}% | *Line total: Rs {{ number_format($lineGrandTotal, 2) }}*
@empty
No line items.
@endforelse

*Grand total* — Rs {{ number_format((float) $sale->total_amount, 2) }}
