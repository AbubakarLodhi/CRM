<?php

namespace App\Support;

use App\Enums\CreditReminderScheduleType;

class CreditReminderEmailCaption
{
    public const PDF_FOOTER_LINE = 'For further details, please download the attached PDF.';

    /**
     * Short email / WhatsApp caption for credit payment reminders.
     */
    public static function forSchedule(
        ?CreditReminderScheduleType $schedule,
        string $recipientRole = 'customer',
    ): string {
        $isAdmin = $recipientRole === 'admin';

        return match ($schedule) {
            CreditReminderScheduleType::BeforeDueDate => $isAdmin
                ? 'A customer payment is due soon. The full invoice and reminder details are in the attached PDF.'
                : 'Your payment is due soon. Please find the full details in the attached PDF.',
            CreditReminderScheduleType::OnDueDate => $isAdmin
                ? 'A customer payment is due today. The full invoice and reminder details are in the attached PDF.'
                : 'Your payment is due today. Please find the full details in the attached PDF.',
            CreditReminderScheduleType::AfterDueDate => $isAdmin
                ? 'A customer payment is overdue. The full invoice and reminder details are in the attached PDF.'
                : 'Your payment is overdue. Please find the full details in the attached PDF.',
            CreditReminderScheduleType::Recurring => $isAdmin
                ? 'Credit payment follow-up for a customer. The full invoice and reminder details are in the attached PDF.'
                : 'This is a payment reminder for your credit invoice. Please find the full details in the attached PDF.',
            default => $isAdmin
                ? 'Credit payment reminder for a customer. The full invoice and reminder details are in the attached PDF.'
                : 'This is a payment reminder for your credit invoice. Please find the full details in the attached PDF.',
        };
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public static function fromVariables(array $variables, string $recipientRole = 'customer'): string
    {
        $schedule = isset($variables['schedule_type'])
            ? CreditReminderScheduleType::tryFrom((string) $variables['schedule_type'])
            : null;

        return self::forSchedule($schedule, $recipientRole);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public static function emailBody(array $variables, string $recipientRole = 'customer'): string
    {
        return self::fromVariables($variables, $recipientRole) . "\n\n" . self::PDF_FOOTER_LINE;
    }
}
