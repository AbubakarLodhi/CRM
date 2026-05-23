@php
    $customer = $sale->customer;
    $merchant = $sale->merchant;
    $dueDate = $sale->due_date?->format('d/m/Y') ?? '—';
    $isAdmin = $recipientRole === 'admin';
    $total = number_format((float) $sale->total_amount, 2);
    $paid = number_format((float) $sale->paid_amount, 2);
    $due = number_format((float) $sale->due_amount, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credit Payment Reminder</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="640" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08);">
                <tr>
                    <td style="background:#1e40af;color:#ffffff;padding:20px 24px;">
                        @if($merchantLogoUrl)
                            <img src="{{ $merchantLogoUrl }}" alt="{{ $merchant?->name }}" style="max-height:48px;margin-bottom:12px;">
                        @endif
                        <h1 style="margin:0;font-size:20px;font-weight:600;">
                            {{ $isAdmin ? 'Customer payment reminder' : 'Payment reminder' }}
                        </h1>
                        <p style="margin:8px 0 0;font-size:14px;opacity:.9;">
                            {{ $merchant?->name }}
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        @if(!empty($templateHtml))
                            {!! $templateHtml !!}
                        @else
                        @if($isAdmin)
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                                Follow up with <strong>{{ $customer?->name ?? 'the customer' }}</strong> if payment is still outstanding.
                            </p>
                        @else
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                                Dear <strong>{{ $customer?->name ?? 'Customer' }}</strong>,
                                please make payment by <strong>{{ $dueDate }}</strong>.
                            </p>
                        @endif

                        <h2 style="font-size:16px;margin:24px 0 12px;color:#1e40af;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Customer details</h2>
                        <table width="100%" style="font-size:14px;margin-bottom:20px;">
                            <tr><td style="padding:4px 0;color:#64748b;width:140px;">Name</td><td>{{ $customer?->name ?? '—' }}</td></tr>
                            <tr><td style="padding:4px 0;color:#64748b;">Email</td><td>{{ $customer?->email ?? '—' }}</td></tr>
                            <tr><td style="padding:4px 0;color:#64748b;">Phone</td><td>{{ $customer?->phone ?? '—' }}</td></tr>
                            <tr><td style="padding:4px 0;color:#64748b;">Reference</td><td>{{ $customer?->reference ?? '—' }}</td></tr>
                            <tr><td style="padding:4px 0;color:#64748b;">Address</td><td>{{ $customer?->address ?? '—' }}</td></tr>
                        </table>

                        <h2 style="font-size:16px;margin:24px 0 12px;color:#1e40af;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Invoice details</h2>
                        <table width="100%" style="font-size:14px;margin-bottom:20px;">
                            <tr><td style="padding:4px 0;color:#64748b;width:140px;">Invoice #</td><td><strong>{{ $sale->sale_no }}</strong></td></tr>
                            <tr><td style="padding:4px 0;color:#64748b;">Sale date</td><td>{{ $sale->sale_date?->format('d/m/Y') ?? '—' }}</td></tr>
                            <tr><td style="padding:4px 0;color:#64748b;">Due date</td><td><strong>{{ $dueDate }}</strong></td></tr>
                        </table>

                        <h2 style="font-size:16px;margin:24px 0 12px;color:#1e40af;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Products</h2>
                        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:13px;margin-bottom:20px;">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th align="left" style="border-bottom:1px solid #e2e8f0;">Product</th>
                                    <th align="center" style="border-bottom:1px solid #e2e8f0;">Qty</th>
                                    <th align="right" style="border-bottom:1px solid #e2e8f0;">Unit price</th>
                                    <th align="right" style="border-bottom:1px solid #e2e8f0;">Line total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td style="border-bottom:1px solid #e2e8f0;">{{ $item->product?->name ?? 'Item' }}</td>
                                        <td align="center" style="border-bottom:1px solid #e2e8f0;">{{ number_format((float) $item->quantity, 2) }}</td>
                                        <td align="right" style="border-bottom:1px solid #e2e8f0;">PKR {{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td align="right" style="border-bottom:1px solid #e2e8f0;">PKR {{ number_format((float) $item->line_total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" style="padding:12px;color:#64748b;">No line items.</td></tr>
                                @endforelse
                            </tbody>
                        </table>

                        <table width="100%" style="font-size:14px;background:#f8fafc;border-radius:6px;padding:16px;margin-bottom:24px;">
                            <tr>
                                <td style="padding:4px 0;color:#64748b;">Total amount</td>
                                <td align="right"><strong>PKR {{ $total }}</strong></td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#64748b;">Amount paid</td>
                                <td align="right">PKR {{ $paid }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#64748b;">Outstanding balance</td>
                                <td align="right" style="color:#b45309;font-weight:bold;">PKR {{ $due }}</td>
                            </tr>
                        </table>

                        <p style="margin:0;font-size:14px;color:#475569;line-height:1.5;">
                            @if($isAdmin)
                                This is an automated reminder for sale <strong>{{ $sale->sale_no }}</strong>. Contact the customer if needed.
                            @else
                                Thank you for your business. Please settle the outstanding balance by the due date above.
                            @endif
                        </p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="background:#f8fafc;padding:16px 24px;font-size:12px;color:#64748b;text-align:center;">
                        {{ $merchant?->name }} · {{ $merchant?->email }} · {{ $merchant?->phone }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
