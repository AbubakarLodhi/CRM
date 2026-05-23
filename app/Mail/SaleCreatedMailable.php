<?php

namespace App\Mail;

use App\Models\NotificationTemplate;
use App\Models\Sale;
use App\Support\NotificationMessageRenderer;
use App\Support\NotificationTemplateResolver;
use App\Support\NotificationTemplateVariableBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;

class SaleCreatedMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ?NotificationTemplate $template = null;

    public HtmlString $templateHtml;

    public HtmlString $invoiceHtml;

    public string $subjectLine;

    public function __construct(
        public Sale $sale,
        ?NotificationTemplate $template = null,
    ) {
        $this->sale->loadMissing([
            'customer',
            'merchant.logo',
            'merchant.settings',
            'items.product',
            'items.variants.variant',
            'payments',
        ]);

        $this->template = $template ?? NotificationTemplateResolver::resolve(
            'sale_created',
            $this->sale->merchant_id,
        );

        $variables = NotificationTemplateVariableBuilder::mergeTestPayload(
            $this->templateTestPayload(),
            NotificationTemplateVariableBuilder::forSale($this->sale),
        );

        $this->subjectLine = $this->template
            ? NotificationMessageRenderer::renderSubject($this->template, $variables, 'Sale Created')
            : 'Sale Created';

        $this->invoiceHtml = new HtmlString($variables['invoice_html'] ?? '');

        $this->templateHtml = new HtmlString(
            $this->template
                ? NotificationMessageRenderer::renderHtmlBody($this->template, $variables)
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

    /**
     * @return array<string, mixed>|null
     */
    private function templateTestPayload(): ?array
    {
        $payload = $this->template?->meta['test_payload'] ?? null;

        if (is_array($payload)) {
            return $payload;
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
