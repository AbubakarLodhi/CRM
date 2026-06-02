@php
    $sale = $sale->loadMissing(['items.product', 'items.variants.variant']);
@endphp
<table class="items-table">
    <thead>
        <tr>
            <th class="col-num">#</th>
            <th class="col-product">Product</th>
            <th class="col-money">Price</th>
            <th class="col-qty">Qty</th>
            <th class="col-money">Subtotal</th>
            <th class="col-pct">Disc.</th>
            <th class="col-pct">Tax</th>
            <th class="col-money">Total</th>
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
                <td class="col-num">{{ $i + 1 }}</td>
                <td class="col-product">
                    <strong>{{ $item->product?->name ?? 'Item' }}</strong>
                    @if($item->variants->first()?->variant?->name)
                        <br><span class="muted">{{ $item->variants->first()->variant->name }}</span>
                    @endif
                </td>
                <td class="col-money">Rs {{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="col-qty">{{ number_format((float) $item->quantity, 2) }}</td>
                <td class="col-money">Rs {{ number_format($lineTotal, 2) }}</td>
                <td class="col-pct">{{ number_format($discountRate, 2) }}%</td>
                <td class="col-pct">{{ number_format($taxRate, 2) }}%</td>
                <td class="col-money"><strong>Rs {{ number_format($lineGrandTotal, 2) }}</strong></td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="muted">No line items.</td>
            </tr>
        @endforelse
    </tbody>
</table>
