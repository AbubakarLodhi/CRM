<?php

namespace App\Services;

use App\Models\CreditReminder;
use App\Models\NotificationTemplate;
use App\Models\Sale;
use App\Support\NotificationTemplateVariableBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreditReminderPdfGenerator
{
    /**
     * @return array{bytes: string, filename: string}
     */
    public function generate(
        Sale $sale,
        CreditReminder $reminder,
        NotificationTemplate $template,
        string $recipientRole = 'customer',
    ): array {
        $sale->loadMissing([
            'customer',
            'merchant.logo',
            'merchant.settings',
            'items.product',
            'items.variants.variant',
            'payments',
        ]);

        $reminder->loadMissing('template');

        $variables = NotificationTemplateVariableBuilder::forCreditReminder(
            $sale,
            $reminder,
            $recipientRole,
        );

        $logoPath = $sale->merchant?->logo?->photo_url;
        $logoAbsolutePath = ($logoPath && Storage::disk('public')->exists($logoPath))
            ? Storage::disk('public')->path($logoPath)
            : null;

        $bytes = Pdf::loadView('pdf.credit-payment-reminder', [
            'sale' => $sale,
            'reminder' => $reminder,
            'recipientRole' => $recipientRole,
            'variables' => $variables,
            'merchantLogoPath' => $logoAbsolutePath,
        ])
            ->setPaper('a4', 'portrait')
            ->output();

        return [
            'bytes' => $bytes,
            'filename' => self::attachmentFilename($sale),
        ];
    }

    public static function attachmentFilename(Sale $sale): string
    {
        $invoiceNo = Str::of((string) $sale->sale_no)
            ->replace('#', '')
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '_')
            ->trim('_')
            ->toString();

        if ($invoiceNo === '') {
            $invoiceNo = 'invoice';
        }

        return "reminder_invoice_{$invoiceNo}.pdf";
    }
}
