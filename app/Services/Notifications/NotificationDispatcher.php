<?php

namespace App\Services\Notifications;

use App\Mail\CreditSaleReminderMailable;
use App\Mail\SaleCreatedMailable;
use App\Models\CreditReminder;
use App\Models\NotificationTemplate;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Support\NotificationMessageRenderer;
use App\Support\NotificationTemplateChannels;
use App\Support\NotificationTemplateResolver;
use App\Support\NotificationTemplateVariableBuilder;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationDispatcher
{
    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    public function dispatchSaleCreated(Sale $sale): NotificationDispatchResult
    {
        $sale->loadMissing(['customer', 'merchant']);

        $template = NotificationTemplateResolver::resolve(
            'sale_created',
            $sale->merchant_id,
        );

        if (! $template) {
            $result = new NotificationDispatchResult;
            $result->addSkipped('sale_created: no active notification template');

            return $result;
        }

        $variables = NotificationTemplateVariableBuilder::mergeTestPayload(
            $this->testPayload($template),
            NotificationTemplateVariableBuilder::forSale($sale),
        );

        return $this->dispatchToParty(
            template: $template,
            event: 'sale_created',
            merchantId: $sale->merchant_id,
            variables: $variables,
            email: $sale->customer?->email,
            phone: $sale->customer?->phone,
            role: 'customer',
            mailableFactory: fn () => new SaleCreatedMailable($sale),
        );
    }

    public function dispatchPurchaseCreated(Purchase $purchase): NotificationDispatchResult
    {
        $purchase->loadMissing(['vendor', 'merchant']);

        $template = NotificationTemplateResolver::resolve(
            'purchase_created',
            $purchase->merchant_id,
        );

        if (! $template) {
            $result = new NotificationDispatchResult;
            $result->addSkipped('purchase_created: no active notification template');

            return $result;
        }

        $variables = NotificationTemplateVariableBuilder::mergeTestPayload(
            $this->testPayload($template),
            NotificationTemplateVariableBuilder::forPurchase($purchase),
        );

        return $this->dispatchToParty(
            template: $template,
            event: 'purchase_created',
            merchantId: $purchase->merchant_id,
            variables: $variables,
            email: $purchase->vendor?->email,
            phone: $purchase->vendor?->phone,
            role: 'vendor',
        );
    }

    public function dispatchPaymentReceived(Sale|Purchase $document, Payment $payment): NotificationDispatchResult
    {
        $merchantId = $document->merchant_id;
        $document->loadMissing($document instanceof Sale ? ['customer', 'merchant'] : ['vendor', 'merchant']);

        $template = NotificationTemplateResolver::resolve(
            'payment_received',
            $merchantId,
        );

        if (! $template) {
            $result = new NotificationDispatchResult;
            $result->addSkipped('payment_received: no active notification template');

            return $result;
        }

        $variables = NotificationTemplateVariableBuilder::mergeTestPayload(
            $this->testPayload($template),
            $document instanceof Sale
                ? NotificationTemplateVariableBuilder::forPayment($document, $payment)
                : NotificationTemplateVariableBuilder::forPayment($document, $payment),
        );

        if ($document instanceof Sale) {
            return $this->dispatchToParty(
                template: $template,
                event: 'payment_received',
                merchantId: $merchantId,
                variables: $variables,
                email: $document->customer?->email,
                phone: $document->customer?->phone,
                role: 'customer',
            );
        }

        return $this->dispatchToParty(
            template: $template,
            event: 'payment_received',
            merchantId: $merchantId,
            variables: $variables,
            email: $document->vendor?->email,
            phone: $document->vendor?->phone,
            role: 'vendor',
        );
    }

    public function dispatchCreditReminder(
        Sale $sale,
        CreditReminder $reminder,
        NotificationTemplate $template,
        string $recipientRole,
        ?string $email,
        ?string $phone,
    ): NotificationDispatchResult {
        $variables = NotificationTemplateVariableBuilder::mergeTestPayload(
            $this->testPayload($template),
            NotificationTemplateVariableBuilder::forCreditReminder($sale, $reminder, $recipientRole),
        );

        return $this->dispatchToParty(
            template: $template,
            event: 'credit_payment_reminder',
            merchantId: $sale->merchant_id,
            variables: $variables,
            email: $email,
            phone: $phone,
            role: $recipientRole,
            mailableFactory: fn () => new CreditSaleReminderMailable($sale, $reminder, $template, $recipientRole),
        );
    }

    /**
     * @param  array<string, mixed>  $variables
     * @param  callable(): \Illuminate\Mail\Mailable|null|null  $mailableFactory
     */
    protected function dispatchToParty(
        NotificationTemplate $template,
        string $event,
        ?string $merchantId,
        array $variables,
        ?string $email,
        ?string $phone,
        string $role,
        ?callable $mailableFactory = null,
    ): NotificationDispatchResult {
        $result = new NotificationDispatchResult;
        $channels = NotificationTemplateChannels::normalize($template->channels);

        if ($channels === []) {
            $result->addSkipped("{$event}/{$role}: template has no channels");

            return $result;
        }

        $sentAny = false;

        if (in_array('email', $channels, true)) {
            if (filled($email)) {
                try {
                    if ($mailableFactory) {
                        $mailable = $mailableFactory();
                        if ($mailable instanceof ShouldQueue) {
                            Mail::to($email)->queue($mailable);
                        } else {
                            Mail::to($email)->send($mailable);
                        }
                    } else {
                        $subject = NotificationMessageRenderer::renderSubject($template, $variables, ucfirst(str_replace('_', ' ', $event)));
                        $html = NotificationMessageRenderer::renderHtmlBody($template, $variables);
                        Mail::html($html, function ($message) use ($email, $subject) {
                            $message->to($email)->subject($subject);
                        });
                    }
                    $result->addSent("email/{$role}: {$email}");
                    $sentAny = true;
                } catch (\Throwable $exception) {
                    $result->addSkipped("email/{$role}: {$exception->getMessage()}");
                    Log::error('Notification email failed', [
                        'event' => $event,
                        'role' => $role,
                        'error' => $exception->getMessage(),
                    ]);
                }
            } else {
                $result->addSkipped("email/{$role}: no email on file");
            }
        }

        if (in_array('whatsapp', $channels, true) && $this->whatsApp->isEnabled()) {
            $resolvedPhone = $this->whatsApp->resolveRecipient($phone);

            if ($resolvedPhone) {
                $subject = NotificationMessageRenderer::renderSubject(
                    $template,
                    $variables,
                    ucfirst(str_replace('_', ' ', $event)),
                );
                $body = NotificationMessageRenderer::renderWhatsAppBody($template, $variables);
                $waResult = $this->whatsApp->sendText(
                    to: $phone ?? '',
                    body: $body,
                    merchantId: $merchantId,
                    subject: $subject,
                    payload: [
                        'event' => $event,
                        'role' => $role,
                        'template_id' => $template->id,
                    ],
                );

                if ($waResult->success) {
                    if ($waResult->simulated) {
                        $label = "whatsapp/{$role}: +{$waResult->to} (logged only — not delivered to WhatsApp app)";
                    } elseif (config('whatsapp.test_mode')) {
                        $label = "whatsapp/{$role}: +{$waResult->to} (test mode)";
                    } else {
                        $label = "whatsapp/{$role}: +{$waResult->to}";
                    }
                    $result->addSent($label);
                    $sentAny = true;
                } else {
                    $result->addSkipped("whatsapp/{$role}: " . ($waResult->error ?? 'failed'));
                }
            } else {
                $result->addSkipped("whatsapp/{$role}: no phone on file");
            }
        }

        if (! $sentAny && $result->sent === []) {
            $result->success = false;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function testPayload(NotificationTemplate $template): ?array
    {
        $payload = $template->meta['test_payload'] ?? null;

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
