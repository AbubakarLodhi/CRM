<?php

namespace App\Support;

use App\Models\CreditReminder;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class NotificationTemplateVariableBuilder
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function forSale(Sale $sale, array $extra = []): array
    {
        $sale->loadMissing([
            'customer',
            'merchant.logo',
            'merchant.settings',
            'items.product',
            'items.variants.variant',
            'payments',
        ]);

        $payments = self::sortedPayments($sale->payments);
        $invoiceHtml = self::renderSaleInvoice($sale);

        $base = [
            'current_date' => now()->format('d/m/Y'),
            'customer_name' => $sale->customer?->name,
            'customer_email' => $sale->customer?->email,
            'customer_phone_no' => $sale->customer?->phone,
            'customer_reference' => $sale->customer?->reference,
            'customer_address' => $sale->customer?->address,
            'sale_no' => $sale->sale_no,
            'sale_date' => $sale->sale_date?->format('d/m/Y') ?? '—',
            'due_date' => $sale->due_date?->format('d/m/Y') ?? '—',
            'payment_type' => $sale->payment_type,
            'payment_type_label' => ucfirst((string) $sale->payment_type),
            'sale_notes' => $sale->notes,
            'subtotal' => number_format((float) $sale->subtotal, 2),
            'total_amount' => number_format((float) $sale->total_amount, 2),
            'paid_amount' => number_format((float) $sale->paid_amount, 2),
            'due_amount' => number_format((float) $sale->due_amount, 2),
            'remaining_amount' => number_format((float) $sale->due_amount, 2),
            'merchant_name' => $sale->merchant?->name,
            'merchant_email' => $sale->merchant?->email,
            'merchant_phone_no' => $sale->merchant?->phone,
            'merchant_whatsapp_no' => $sale->merchant?->whatsapp_number,
            'merchant_logo_url' => self::merchantLogoUrl($sale->merchant?->logo?->photo_url),
            'payment_history_html' => self::paymentHistoryHtml($payments),
            'payment_history_text' => self::paymentHistoryText($payments),
            'invoice_html' => $invoiceHtml,
            'document_no' => $sale->sale_no,
            'document_type' => 'sale',
            'document_date' => $sale->sale_date?->format('d/m/Y') ?? '—',
            'party_name' => $sale->customer?->name,
            'party_email' => $sale->customer?->email,
            'party_phone_no' => $sale->customer?->phone,
        ];

        return array_merge($base, $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function forCreditReminder(
        Sale $sale,
        CreditReminder $reminder,
        string $recipientRole = 'customer',
    ): array {
        $reminder->loadMissing('template');
        $schedule = $reminder->template?->scheduleType();

        return self::forSale($sale, [
            'remind_at' => $reminder->remind_at?->format('d/m/Y') ?? '—',
            'reminder_name' => $reminder->template?->name ?? 'Payment reminder',
            'schedule_label' => $schedule?->label(),
            'schedule_type' => $schedule?->value,
            'recipient_role' => $recipientRole,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function forPurchase(Purchase $purchase, array $extra = []): array
    {
        $purchase->loadMissing([
            'vendor',
            'merchant.logo',
            'merchant.settings',
            'items.product',
            'payments',
        ]);

        $payments = self::sortedPayments($purchase->payments);
        $invoiceHtml = self::renderPurchaseInvoice($purchase);

        $base = [
            'current_date' => now()->format('d/m/Y'),
            'vendor_name' => $purchase->vendor?->name,
            'vendor_email' => $purchase->vendor?->email,
            'vendor_phone_no' => $purchase->vendor?->phone,
            'purchase_no' => $purchase->purchase_no,
            'purchase_date' => $purchase->purchase_date?->format('d/m/Y') ?? '—',
            'payment_type' => $purchase->payment_type,
            'payment_type_label' => ucfirst((string) $purchase->payment_type),
            'purchase_notes' => $purchase->notes,
            'subtotal' => number_format((float) $purchase->subtotal, 2),
            'total_amount' => number_format((float) $purchase->total_amount, 2),
            'paid_amount' => number_format((float) $purchase->paid_amount, 2),
            'due_amount' => number_format((float) $purchase->due_amount, 2),
            'remaining_amount' => number_format((float) $purchase->due_amount, 2),
            'merchant_name' => $purchase->merchant?->name,
            'merchant_email' => $purchase->merchant?->email,
            'merchant_phone_no' => $purchase->merchant?->phone,
            'merchant_whatsapp_no' => $purchase->merchant?->whatsapp_number,
            'merchant_logo_url' => self::merchantLogoUrl($purchase->merchant?->logo?->photo_url),
            'payment_history_html' => self::paymentHistoryHtml($payments),
            'payment_history_text' => self::paymentHistoryText($payments),
            'invoice_html' => $invoiceHtml,
            'document_no' => $purchase->purchase_no,
            'document_type' => 'purchase',
            'document_date' => $purchase->purchase_date?->format('d/m/Y') ?? '—',
            'party_name' => $purchase->vendor?->name,
            'party_email' => $purchase->vendor?->email,
            'party_phone_no' => $purchase->vendor?->phone,
        ];

        return array_merge($base, $extra);
    }

    /**
     * @return array<string, mixed>
     */
    public static function forPayment(Sale|Purchase $document, Payment $payment): array
    {
        $paymentDate = $payment->payment_date?->format('d/m/Y') ?? '—';
        $amount = number_format((float) ($payment->amount ?? 0), 2);
        $entryType = ucfirst((string) ($payment->entry_type ?? 'payment'));

        if ($document instanceof Sale) {
            return self::forSale($document, [
                'payment_amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $payment->method,
                'payment_entry_type' => $entryType,
                'payment_reference_no' => $payment->reference_no,
            ]);
        }

        return self::forPurchase($document, [
            'payment_amount' => $amount,
            'payment_date' => $paymentDate,
            'payment_method' => $payment->method,
            'payment_entry_type' => $entryType,
            'payment_reference_no' => $payment->reference_no,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $testPayload
     * @return array<string, mixed>
     */
    public static function mergeTestPayload(?array $testPayload, array $variables): array
    {
        if (! is_array($testPayload) || $testPayload === []) {
            return $variables;
        }

        return array_merge($testPayload, $variables);
    }

    private static function renderSaleInvoice(Sale $sale): string
    {
        return view('emails.partials.sale-invoice', ['sale' => $sale])->render();
    }

    private static function renderPurchaseInvoice(Purchase $purchase): string
    {
        return view('emails.partials.purchase-invoice', ['purchase' => $purchase])->render();
    }

    private static function merchantLogoUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    private static function sortedPayments(?Collection $payments): Collection
    {
        return ($payments ?? collect())
            ->sortBy([
                ['payment_date', 'asc'],
                ['created_at', 'asc'],
            ])
            ->values();
    }

    private static function paymentHistoryHtml(Collection $payments): string
    {
        if ($payments->isEmpty()) {
            return '<p style="margin:0;font-size:13px;color:#64748b;">No payments recorded yet.</p>';
        }

        $rows = $payments->map(function ($payment) {
            $date = $payment->payment_date?->format('d/m/Y') ?? '—';
            $type = ucfirst((string) ($payment->entry_type ?? 'payment'));
            $amount = number_format((float) ($payment->amount ?? 0), 2);

            return sprintf(
                '<tr>
                    <td style="padding:8px;border-bottom:1px solid #e2e8f0;">%s</td>
                    <td style="padding:8px;border-bottom:1px solid #e2e8f0;">%s</td>
                    <td style="padding:8px;border-bottom:1px solid #e2e8f0;text-align:right;">PKR %s</td>
                </tr>',
                e($date),
                e($type),
                e($amount),
            );
        })->implode('');

        return '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;">'
            . '<thead><tr style="background:#f8fafc;">'
            . '<th align="left" style="padding:8px;border-bottom:1px solid #e2e8f0;">Date</th>'
            . '<th align="left" style="padding:8px;border-bottom:1px solid #e2e8f0;">Type</th>'
            . '<th align="right" style="padding:8px;border-bottom:1px solid #e2e8f0;">Amount</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>';
    }

    private static function paymentHistoryText(Collection $payments): string
    {
        if ($payments->isEmpty()) {
            return 'No payments recorded yet.';
        }

        return $payments->map(function ($payment) {
            $date = $payment->payment_date?->format('d/m/Y') ?? '—';
            $type = ucfirst((string) ($payment->entry_type ?? 'payment'));
            $amount = number_format((float) ($payment->amount ?? 0), 2);

            return "{$date} | {$type} | PKR {$amount}";
        })->implode("\n");
    }
}
