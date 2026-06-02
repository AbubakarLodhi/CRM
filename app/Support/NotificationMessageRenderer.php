<?php

namespace App\Support;

use App\Models\NotificationTemplate;
use App\Models\Sale;
use Illuminate\Support\Facades\Blade;

class NotificationMessageRenderer
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public static function renderSubject(NotificationTemplate $template, array $variables, ?string $fallback = null): string
    {
        if (! filled($template->subject)) {
            return $fallback ?? 'Notification';
        }

        return trim(Blade::render($template->subject, $variables));
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public static function renderHtmlBody(NotificationTemplate $template, array $variables): string
    {
        return Blade::render($template->content, $variables);
    }

    /**
     * WhatsApp text for credit reminders — mirrors the email body (line items, grand total, caption).
     *
     * @param  array<string, mixed>  $variables
     */
    public static function renderCreditReminderWhatsAppBody(
        Sale $sale,
        array $variables,
        string $recipientRole = 'customer',
    ): string {
        return CreditReminderPlainMessageBuilder::forWhatsApp($sale, $variables, $recipientRole);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public static function renderWhatsAppBody(NotificationTemplate $template, array $variables): string
    {
        $whatsappVariables = self::compactVariablesForWhatsApp($variables);

        $html = self::renderHtmlBody($template, $whatsappVariables);
        $text = HtmlToPlainText::convert($html);

        // Twilio WhatsApp limit is 1600 characters per message.
        $maxLength = config('whatsapp.driver') === 'twilio' ? 1500 : 4000;

        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength - 40) . "\n\n… Full details were sent by email.";
        }

        return trim($text);
    }

    /**
     * Drop bulky HTML blocks; keep text-friendly fields for WhatsApp.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    private static function compactVariablesForWhatsApp(array $variables): array
    {
        unset(
            $variables['invoice_html'],
            $variables['payment_history_html'],
            $variables['merchant_logo_url'],
        );

        if (filled($variables['payment_history_text'] ?? null)) {
            $variables['payment_history_html'] = nl2br(e((string) $variables['payment_history_text']));
        }

        return $variables;
    }
}
