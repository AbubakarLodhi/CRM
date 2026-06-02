@php
    use App\Support\CreditReminderEmailCaption;

    $sale = $sale->loadMissing(['items.product', 'items.variants.variant', 'merchant.settings', 'customer']);
    $merchant = $sale->merchant;
    $customer = $sale->customer;
    $isAdmin = ($recipientRole ?? 'customer') === 'admin';
    $companyName = $merchant?->name ?? 'Our company';
    $amountDue = (float) ($sale->due_amount ?? 0);
    $dueDateFormatted = $sale->due_date?->format('d M, Y') ?? '—';
    $supportEmail = $merchant?->email;
    $emailCaption = CreditReminderEmailCaption::fromVariables($variables ?? [], $recipientRole ?? 'customer');
    $dashedLine = '────────────────────────';
@endphp
*{{ $companyName }}*
{{ $isAdmin ? 'is following up on a payment of' : 'is requesting a payment of' }}
*Rs {{ number_format($amountDue, 2) }}*

{{ $dashedLine }}
Reference #     {{ $sale->sale_no }}
Customer        {{ $customer?->name ?? '—' }}
Due date        {{ $dueDateFormatted }}
{{ $dashedLine }}

@include('notifications.partials.sale-line-items-whatsapp', ['sale' => $sale])

{{ $emailCaption }}

For full invoice details, please refer to the credit invoice PDF shared via email.

*Have questions?*
@if($supportEmail)
If you need assistance, reach out to our support team at {{ $supportEmail }}
@elseif($merchant?->phone)
If you need assistance, contact us at {{ $merchant->phone }}
@endif

powered by *{{ $companyName }}*
