<?php

namespace App\Mail;

use App\Models\CreditReminder;
use App\Models\NotificationTemplate;
use App\Models\Sale;
use App\Support\NotificationMessageRenderer;
use App\Support\NotificationTemplateVariableBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;

class CreditSaleReminderMailable extends Mailable
{
    use Queueable, SerializesModels;

    public HtmlString $templateHtml;

    /** @var array<string, mixed> */
    protected array $variables;

    public function __construct(
        public Sale $sale,
        public CreditReminder $reminder,
        public NotificationTemplate $template,
        public string $recipientRole = 'customer',
    ) {
        $this->sale->loadMissing([
            'customer',
            'merchant.logo',
            'merchant.settings',
            'items.product',
            'items.variants.variant',
            'payments',
        ]);

        $this->reminder->loadMissing('template');

        $this->variables = NotificationTemplateVariableBuilder::forCreditReminder(
            $this->sale,
            $this->reminder,
            $this->recipientRole,
        );

        $this->templateHtml = new HtmlString(
            NotificationMessageRenderer::renderHtmlBody($this->template, $this->variables)
        );
    }

    public function envelope(): Envelope
    {
        $default = $this->recipientRole === 'admin'
            ? 'Payment follow-up — ' . $this->sale->sale_no
            : 'Payment reminder — ' . $this->sale->sale_no;

        $subject = NotificationMessageRenderer::renderSubject($this->template, $this->variables, $default);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credit-sale-reminder',
            with: [
                'sale' => $this->sale,
                'reminder' => $this->reminder,
                'recipientRole' => $this->recipientRole,
                'merchantLogoUrl' => $this->variables['merchant_logo_url'] ?? null,
                'items' => $this->sale->items,
                'templateHtml' => $this->templateHtml,
            ],
        );
    }
}
