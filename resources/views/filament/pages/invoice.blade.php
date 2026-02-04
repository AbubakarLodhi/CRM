<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    @php
        $isSale = $type === 'sale';

        $invoiceNo   = $isSale ? $record->sale_no       : $record->purchase_no;
        $invoiceDate = $isSale ? $record->sale_date     : $record->purchase_date;

        // Customer for Sale | Supplier for Purchase
        $party = $isSale ? $record->customer : $record->supplier;
    @endphp

    <title>Invoice {{ $invoiceNo }}</title>

    <style>
        body {
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont;
            background: #f8fafc;
            margin: 0;
            padding: 30px;
            color: #0f172a;
        }

        .invoice {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 30px;
        }

        .brand img {
            height: 42px;
        }

        .invoice-meta {
            text-align: right;
            font-size: 14px;
        }

        .card {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        thead th {
            text-align: left;
            font-size: 13px;
            color: #6366f1;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 10px;
        }

        tbody td {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .summary {
            width: 300px;
            margin-left: auto;
            margin-top: 30px;
            font-size: 14px;
        }

        .summary div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .total {
            background: #6366f1;
            color: #fff;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .notes {
            margin-top: 40px;
            font-size: 13px;
            color: #475569;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .invoice {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>

<div class="invoice">

    {{-- HEADER --}}
    <div class="header">
        <div class="brand">
            @if($record->merchant?->logo)
                <img src="{{ asset('storage/'.$record->merchant->logo->photo_url) }}">
            @else
                <strong>{{ $record->merchant?->name }}</strong>
            @endif
        </div>

        <div class="invoice-meta">
            <div><strong>Date:</strong> {{ $invoiceDate?->format('F d, Y') }}</div>
            <div><strong>Invoice #</strong> {{ $invoiceNo }}</div>
        </div>
    </div>

    {{-- COMPANY INFO --}}
    <div class="card">

        {{-- MERCHANT --}}
        <div>
            <strong>{{ $record->merchant?->name }}</strong><br>

            @if($record->merchant?->email)
                Email: {{ $record->merchant->email }}<br>
            @endif

            @if($record->merchant?->phone)
                Phone: {{ $record->merchant->phone }}<br>
            @endif

            @if($record->merchant?->vat_number)
                VAT: {{ $record->merchant->vat_number }}<br>
            @endif

            @if($record->merchant?->address)
                {{ $record->merchant->address }}<br>
            @endif

            @if($record->merchant?->city || $record->merchant?->country)
                {{ $record->merchant->city }} {{ $record->merchant->country }}
            @endif
        </div>

        {{-- CUSTOMER / SUPPLIER --}}
        <div>
            <strong>{{ $party?->name }}</strong><br>

            @if($party?->email)
                Email: {{ $party->email }}<br>
            @endif

            @if($party?->phone)
                Phone: {{ $party->phone }}<br>
            @endif

            {{ $party?->address ?? '—' }}
        </div>

    </div>

    {{-- ITEMS --}}
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Product details</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @foreach($record->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    {{ $item->product?->name }}

                    @if($item->variants->first())
                        <div style="font-size:12px;color:#64748b">
                            {{ $item->variants->first()->variant?->name }}
                        </div>
                    @endif
                </td>
                <td>Rs{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>Rs{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{-- SUMMARY --}}
    <div class="summary">
        <div>
            <span>Net total</span>
            <span>Rs{{ number_format($record->subtotal, 2) }}</span>
        </div>

        <div>
            <span>Tax</span>
            <span>Rs{{ number_format($record->tax, 2) }}</span>
        </div>

        <div class="total">
            <span>Total</span>
            <span>Rs{{ number_format($record->total_amount, 2) }}</span>
        </div>
    </div>

    {{-- NOTES --}}
    @if($record->notes)
        <div class="notes">
            <strong>Notes</strong><br>
            {{ $record->notes }}
        </div>
    @endif

</div>

</body>
</html>
