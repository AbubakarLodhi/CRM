<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    @php
        $isSale = $type === 'sale';

        $invoiceNo   = $isSale ? $record->sale_no       : $record->purchase_no;
        $invoiceDate = $isSale ? $record->sale_date     : $record->purchase_date;

        // Customer for Sale | Vendor for Purchase
        $party = $isSale ? $record->customer : $record->vendor;
        $totalDiscount = 0;
        $totalTax = 0;
        $totalDiscountPercent = 0;
        $totalTaxPercent = 0;

        foreach ($record->items as $item) {
            $lineTotal = (float) ($item->line_total ?? 0);
            $discountRate = (float) ($item->discount ?? 0);
            $taxRate = (float) ($item->tax ?? 0);

            $discountAmount = $lineTotal * ($discountRate / 100);
            $taxableAmount = $lineTotal - $discountAmount;
            $taxAmount = $taxableAmount * ($taxRate / 100);

            $totalDiscount += $discountAmount;
            $totalTax += $taxAmount;
            $totalDiscountPercent += $discountRate;
            $totalTaxPercent += $taxRate;
        }

        $headerGroups = $headerGroups ?? [];
        $footerGroups = $footerGroups ?? [];
        $headerGroupOptions = $headerGroupOptions ?? ['__default' => 'Default (Current Header)'];
        $footerGroupOptions = $footerGroupOptions ?? ['__default' => 'Default (Current Footer)'];
        $selectedHeaderGroup = $selectedHeaderGroup ?? '__default';
        $selectedFooterGroup = $selectedFooterGroup ?? '__default';
        $showDefaultHeader = $showDefaultHeader ?? true;
        $showDefaultFooter = $showDefaultFooter ?? true;
    @endphp

    <title>Invoice {{ $invoiceNo }}</title>

    <style>
        :root {
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
            --accent: #111827;
            --bg: #f3f4f6;
        }

        body {
            font-family: "Georgia", "Times New Roman", serif;
            background: var(--bg);
            margin: 0;
            padding: 30px 0;
            color: var(--ink);
        }

        .invoice {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 48px 54px 60px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.12);
            box-sizing: border-box;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: start;
        }

        .brand {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 45%;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }

        .brand img {
            height: 44px;
            width: auto;
        }

        .invoice-title {
            text-align: right;
            max-width: 45%;
        }

        .invoice-title h1 {
            margin: 0 0 8px;
            font-size: 26px;
            letter-spacing: 2px;
            font-weight: 600;
            color: var(--accent);
        }

        .invoice-title .subtitle {
            font-size: 12px;
            color: var(--muted);
        }


        .party-row {
            margin-top: 26px;
            display: flex;
            justify-content: space-between;
            gap: 24px;
            font-size: 12.5px;
            color: var(--muted);
        }

        .party-row strong {
            color: var(--accent);
        }

        .invoice-meta {
            margin-top: 18px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 28px;
            font-size: 12.5px;
            color: var(--muted);
        }

        .invoice-meta div {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .invoice-meta span.label {
            color: var(--muted);
        }

        .divider {
            margin: 26px 0 14px;
            border-top: 1px solid var(--line);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        thead th {
            text-align: left;
            font-size: 12px;
            color: #fff;
            background: #111827;
            padding: 10px 8px;
            font-weight: 600;
        }

        tbody td {
            padding: 12px 8px;
            border-bottom: 1px solid var(--line);
            font-size: 13px;
            vertical-align: top;
        }

        .item-meta {
            font-size: 11px;
            color: var(--muted);
            margin-top: 4px;
        }

        .summary {
            width: 320px;
            margin-left: auto;
            margin-top: auto;
            font-size: 12.5px;
            color: var(--muted);
            background: #fff;
            padding: 0;
            border-radius: 0;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .summary div {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }

        .summary .grand {
            margin-top: 6px;
            background: #111827;
            padding: 10px 12px;
            border-radius: 6px;
            font-weight: 600;
            color: #fff;
        }


        .notes {
            margin-top: 32px;
            font-size: 12px;
            color: var(--muted);
        }

        .contact-us {
            margin-top: 18px;
            font-size: 12px;
            color: var(--muted);
        }

        .contact-us strong {
            color: var(--accent);
        }

        .footer-gap {
            flex: 1 1 auto;
        }

        .actions {
            position: fixed;
            right: 24px;
            top: 24px;
            display: flex;
            gap: 10px;
            z-index: 10;
            align-items: center;
        }

        .btn {
            border: none;
            background: #111827;
            color: #fff;
            padding: 6px 12px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 12px;
            line-height: 18px;
        }

        .btn.secondary {
            background: #e5e7eb;
            color: #111827;
        }

        .invoice-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 8px 10px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .invoice-controls label {
            font-size: 11px;
            color: #6b7280;
            margin-right: 4px;
        }

        .invoice-controls select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #111827;
            font-size: 12px;
            padding: 4px 8px;
        }

        @media print {
            @page {
                size: A4;
                margin: 12mm;
            }
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .invoice {
                box-shadow: none;
                border-radius: 0;
                padding: 12mm;
                min-height: 100vh;
                height: 100vh;
            }
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="actions">
    <form
        method="GET"
        action="{{ route('invoices.show', ['type' => $type, 'id' => $record->id]) }}"
        class="invoice-controls"
    >
        <label for="header_group">Header</label>
        <select id="header_group" name="header_group" onchange="this.form.submit()">
            @foreach($headerGroupOptions as $groupId => $groupLabel)
                <option value="{{ $groupId }}" @selected((string) $selectedHeaderGroup === (string) $groupId)>
                    {{ $groupLabel }}
                </option>
            @endforeach
        </select>

        <label for="footer_group">Footer</label>
        <select id="footer_group" name="footer_group" onchange="this.form.submit()">
            @foreach($footerGroupOptions as $groupId => $groupLabel)
                <option value="{{ $groupId }}" @selected((string) $selectedFooterGroup === (string) $groupId)>
                    {{ $groupLabel }}
                </option>
            @endforeach
        </select>
    </form>

    <button class="btn" type="button" onclick="window.print()">Print</button>
    <button
        class="btn secondary"
        type="button"
        aria-label="Close"
        onclick="if (window.opener) { window.close(); } else { window.history.back(); }"
    >
        Close
    </button>
</div>

<div class="invoice">

    <div class="header">
        <div class="brand">
            @if($record->merchant?->logo)
                <img src="{{ asset('storage/'.$record->merchant->logo->photo_url) }}">
            @else
                <strong>{{ $record->merchant?->name }}</strong>
            @endif

            @if($showDefaultHeader)
                <div>
                    {{ $record->merchant?->name }}<br>
                    @if($record->merchant?->address)
                        {{ $record->merchant->address }}<br>
                    @endif
                    @if($record->merchant?->city || $record->merchant?->country)
                        {{ $record->merchant->city }} {{ $record->merchant->country }}<br>
                    @endif
                    @if($record->merchant?->phone)
                        {{ $record->merchant->phone }}<br>
                    @endif
                    @if($record->merchant?->email)
                        {{ $record->merchant->email }}<br>
                    @endif
                    @if($record->merchant?->vat_number)
                        VAT: {{ $record->merchant->vat_number }}
                    @endif
                </div>
            @endif

            @foreach($headerGroups as $group)
                <div>
                    <strong>{{ $group['group_name'] }}</strong><br>
                    @foreach($group['fields'] as $field)
                        {{ $field['label'] }}: {{ $field['value'] }}<br>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="invoice-title">
            <h1>INVOICE</h1>
            <div class="subtitle">Invoice# {{ $invoiceNo }}</div>
        </div>
    </div>

    <div class="party-row">
        <div>
            <strong>{{ $isSale ? 'Bill To' : 'Bill From' }}</strong><br>
            {{ $party?->name }}<br>
            @if($party?->email)
                {{ $party->email }}<br>
            @endif
            @if($party?->phone)
                {{ $party->phone }}<br>
            @endif
            {{ $party?->address ?? '—' }}
        </div>
        <div>
            <div class="invoice-meta">
                <div><span class="label">Invoice Date</span><span>{{ $invoiceDate?->format('d/m/Y') }}</span></div>
                <div><span class="label">Invoice #</span><span>{{ $invoiceNo }}</span></div>
                <div><span class="label">Due Date</span><span>{{ $invoiceDate?->format('d/m/Y') }}</span></div>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Item & Description</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Subtotal</th>
            <th>Discount (%)</th>
            <th>Tax (%)</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($record->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    {{ $item->product?->name }}
                    @if($item->variants->first())
                        <div class="item-meta">
                            {{ $item->variants->first()->variant?->name }}
                        </div>
                    @endif
                </td>
                <td>Rs{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>Rs{{ number_format($item->line_total, 2) }}</td>
                <td>{{ number_format((float) ($item->discount ?? 0), 2) }}%</td>
                <td>{{ number_format((float) ($item->tax ?? 0), 2) }}%</td>
                @php
                    $lineTotal = (float) ($item->line_total ?? 0);
                    $discountRate = (float) ($item->discount ?? 0);
                    $taxRate = (float) ($item->tax ?? 0);

                    $discountAmount = $lineTotal * ($discountRate / 100);
                    $taxableAmount = $lineTotal - $discountAmount;
                    $taxAmount = $taxableAmount * ($taxRate / 100);

                    $lineGrandTotal = $taxableAmount + $taxAmount;
                @endphp
                <td>Rs{{ number_format($lineGrandTotal, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="footer-gap"></div>

    <div class="summary" style="margin-bottom: 24px;">
        <div>
            <span>Sub Total</span>
            <span>Rs{{ number_format($record->subtotal, 2) }}</span>
        </div>

        <div>
            <span>Total Discount</span>
            <span>Rs{{ number_format($totalDiscount, 2) }}</span>
        </div>

        <div>
            <span>Total Tax</span>
            <span>Rs{{ number_format($totalTax, 2) }}</span>
        </div>

        <div class="grand">
            <span>Grand Total</span>
            <span>Rs{{ number_format($record->total_amount, 2) }}</span>
        </div>

    </div>

    @if($record->notes)
        <div class="notes">
            <strong>Notes</strong><br>
            {{ $record->notes }}
        </div>
    @endif

    @if($showDefaultFooter && ($record->merchant?->email || $record->merchant?->phone || $record->merchant?->whatsapp_number))
        <div class="contact-us">
            <strong>Contact Us</strong><br>
            @if($record->merchant?->email)
                Email: {{ $record->merchant->email }}<br>
            @endif
            @if($record->merchant?->phone)
                Phone: {{ $record->merchant->phone }}<br>
            @endif
            @if($record->merchant?->whatsapp_number)
                WhatsApp: {{ $record->merchant->whatsapp_number }}
            @endif
        </div>
    @endif

    @if(! empty($footerGroups))
        <div class="contact-us">
            @foreach($footerGroups as $group)
                <strong>{{ $group['group_name'] }}</strong><br>
                @foreach($group['fields'] as $field)
                    {{ $field['label'] }}: {{ $field['value'] }}<br>
                @endforeach
                @if(! $loop->last)
                    <br>
                @endif
            @endforeach
        </div>
    @endif

</div>

</body>
</html>
