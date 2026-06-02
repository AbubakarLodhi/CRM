<?php

namespace App\Support;

use App\Models\Sale;

class CreditReminderPlainMessageBuilder
{
    /**
     * Plain-text credit reminder (WhatsApp) — same structure as the email body.
     *
     * @param  array<string, mixed>  $variables
     */
    public static function forWhatsApp(
        Sale $sale,
        array $variables,
        string $recipientRole = 'customer',
    ): string {
        $text = view('notifications.credit-reminder-whatsapp', [
            'sale' => $sale,
            'variables' => $variables,
            'recipientRole' => $recipientRole,
        ])->render();

        $text = preg_replace("/\n{3,}/", "\n\n", trim($text)) ?? trim($text);

        $maxLength = config('whatsapp.driver') === 'twilio' ? 1500 : 4000;

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $sale->loadMissing(['merchant', 'customer']);
        $companyName = $sale->merchant?->name ?? 'Our company';
        $caption = CreditReminderEmailCaption::fromVariables($variables, $recipientRole);
        $footer = CreditReminderEmailCaption::PDF_FOOTER_LINE;
        $grandTotal = '*Grand total* — Rs ' . number_format((float) $sale->total_amount, 2);
        $amountDue = '*Rs ' . number_format((float) $sale->due_amount, 2) . '*';
        $header = "*{$companyName}*\n"
            . ($recipientRole === 'admin' ? 'is following up on a payment of' : 'is requesting a payment of')
            . "\n{$amountDue}\n\n";

        $tail = "\n\n{$grandTotal}\n\n{$caption}\n\n{$footer}";
        $budget = $maxLength - strlen($tail) - 40;
        $truncated = $header . substr($text, 0, max(0, $budget));

        return rtrim($truncated) . "\n\n…(message shortened)" . $tail;
    }
}
