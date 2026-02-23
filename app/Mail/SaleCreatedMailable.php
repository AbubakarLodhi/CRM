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

        $merchantLogoUrl = 'https://plus.unsplash.com/premium_photo-1666900440561-94dcb6865554?q=80&w=627&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';

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
}
