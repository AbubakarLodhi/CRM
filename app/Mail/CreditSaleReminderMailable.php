<?php

namespace App\Mail;

use App\Models\CreditReminder;
use App\Models\NotificationTemplate;
use App\Models\Sale;
use App\Services\CreditReminderPdfGenerator;
use App\Support\CreditReminderEmailCaption;
use App\Support\NotificationMessageRenderer;
use App\Support\NotificationTemplateVariableBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreditSaleReminderMailable extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailCaption;

    public string $pdfFooterLine;

    protected string $pdfBytes;

    protected string $pdfFilename;

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

        $this->emailCaption = CreditReminderEmailCaption::fromVariables(
            $this->variables,
            $this->recipientRole,
        );

        $this->pdfFooterLine = CreditReminderEmailCaption::PDF_FOOTER_LINE;

        $pdf = app(CreditReminderPdfGenerator::class)->generate(
            $this->sale,
            $this->reminder,
            $this->template,
            $this->recipientRole,
        );

        $this->pdfBytes = $pdf['bytes'];
        $this->pdfFilename = $pdf['filename'];
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
                'merchant' => $this->sale->merchant,
                'emailCaption' => $this->emailCaption,
                'pdfFooterLine' => $this->pdfFooterLine,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBytes, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
