<?php

namespace App\Mail;

use App\Models\NotificationTemplate;
use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class SaleCreatedMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ?NotificationTemplate $template = null;
    public HtmlString $templateHtml;
    public HtmlString $invoiceHtml;
    public string $subjectLine;

    public function __construct(public Sale $sale)
    {
        $this->sale->loadMissing([
            'customer',
            'merchant.logo', // IMPORTANT
            'merchant.settings',
            'items.product',
        ]);

        $this->template = NotificationTemplate::query()
            ->where('event', 'sale_created')
            ->where('channel', 'email')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('merchant_id', $this->sale->merchant_id)
                    ->orWhereNull('merchant_id');
            })
            ->orderByRaw('merchant_id is null')
            ->latest('updated_at')
            ->first();

        $this->subjectLine = $this->template?->subject ?: 'Sale Created';

        $this->invoiceHtml = new HtmlString(
            view('emails.partials.sale-invoice', [
                'sale' => $this->sale,
            ])->render()
        );

        $templateVars = $this->templateVariables();

        $this->templateHtml = new HtmlString(
            $this->template
                ? Blade::render($this->template->content, $templateVars)
                : ''
        );
    }

    public function hasTemplate(): bool
    {
        return (bool) $this->template;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sale-created',
            with: [
                'template_html' => $this->templateHtml,
                'invoice_html' => $this->invoiceHtml,
            ],
        );
    }

    private function templateVariables(): array
    {
        $payload = $this->templateTestPayload();

        if (! is_array($payload)) {
            $payload = [];
        }

        $sale = $this->sale;
        $payments = ($sale->payments ?? collect())
            ->sortBy([
                ['payment_date', 'asc'],
                ['created_at', 'asc'],
            ])
            ->values();

        $merchantLogoUrl = null;
        if ($sale->merchant?->logo?->photo_url) {

            $merchantLogoUrl = Storage::disk('public')
                ->url($sale->merchant->logo->photo_url);
        }
       // dd($merchantLogoUrl);

        $mapping = [
            'customer_name'     => $sale->customer?->name,
            'customer_email'    => $sale->customer?->email,
            'customer_phone_no' => $sale->customer?->phone,
            'sale_no'           => $sale->sale_no,
            'sale_date'         => $sale->sale_date?->format('Y-m-d'),
            'subtotal'          => $sale->subtotal,
            'total_amount'      => $sale->total_amount,
            'merchant_name'     => $sale->merchant?->name,
            'merchant_email'    => $sale->merchant?->email,
            'merchant_phone_no' => $sale->merchant?->phone,
            'merchant_logo_url' => $merchantLogoUrl,
            'payment_type'      => $sale->payment_type,
            'payment_history_html' => $this->paymentHistoryHtml($payments),
            'payment_history_text' => $this->paymentHistoryText($payments),
            'invoice_html'      => $this->invoiceHtml->toHtml(),
        ];

        return array_merge($payload, $mapping);
    }

    private function templateTestPayload(): array
    {
        $payload = $this->template?->meta['test_payload'] ?? null;

        if (is_array($payload)) {
            return $payload;
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function paymentHistoryHtml(\Illuminate\Support\Collection $payments): string
    {
        if ($payments->isEmpty()) {
            return '<p>No payment history available.</p>';
        }

        $rows = $payments->map(function ($payment) {
            $date = $payment->payment_date?->format('d/m/Y') ?? '—';
            $type = ucfirst((string) ($payment->entry_type ?? 'payment'));
            $amount = number_format((float) ($payment->amount ?? 0), 2);

            return sprintf(
                '<tr><td style="padding:6px 0;">%s</td><td style="padding:6px 0;">%s</td><td style="padding:6px 0;text-align:right;">Rs%s</td></tr>',
                e($date),
                e($type),
                e($amount),
            );
        })->implode('');

        return '<table style="width:100%;border-collapse:collapse;font-size:12px;">'
            . '<thead><tr><th style="text-align:left;padding:6px 0;">Date</th><th style="text-align:left;padding:6px 0;">Type</th><th style="text-align:right;padding:6px 0;">Amount</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>';
    }

    private function paymentHistoryText(\Illuminate\Support\Collection $payments): string
    {
        if ($payments->isEmpty()) {
            return 'No payment history available.';
        }

        return $payments->map(function ($payment) {
            $date = $payment->payment_date?->format('d/m/Y') ?? '—';
            $type = ucfirst((string) ($payment->entry_type ?? 'payment'));
            $amount = number_format((float) ($payment->amount ?? 0), 2);

            return "{$date} | {$type} | Rs{$amount}";
        })->implode("\n");
    }
}
