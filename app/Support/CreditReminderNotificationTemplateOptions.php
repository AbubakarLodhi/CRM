<?php

namespace App\Support;

use App\Models\NotificationTemplate;
use App\Support\NotificationTemplateEvents;

class CreditReminderNotificationTemplateOptions
{
    /**
     * All email notification templates available for credit reminders (any event, including custom).
     *
     * @return array<string, string>
     */
    public static function forMerchant(?string $merchantId = null): array
    {
        $merchantId ??= CreditReminderMerchantContext::resolveMerchantId();

        if (! $merchantId) {
            return [];
        }

        return NotificationTemplate::query()
            ->where(function ($query) {
                $query->whereJsonContains('channels', 'email')
                    ->orWhereJsonContains('channels', 'whatsapp');
            })
            ->where(function ($query) use ($merchantId) {
                $query->where('merchant_id', $merchantId)
                    ->orWhereNull('merchant_id');
            })
            ->orderByDesc('is_active')
            ->orderBy('subject')
            ->get()
            ->mapWithKeys(fn (NotificationTemplate $template) => [
                $template->id => self::formatLabel($template),
            ])
            ->all();
    }

    public static function labelFor(?string $templateId): ?string
    {
        if (! $templateId) {
            return null;
        }

        $template = NotificationTemplate::query()->find($templateId);

        return $template ? self::formatLabel($template) : null;
    }

    private static function formatLabel(NotificationTemplate $template): string
    {
        $events = collect($template->events ?? [])
            ->map(fn (string $event) => NotificationTemplateEvents::label($event))
            ->implode(', ');

        $label = $template->subject ?: 'Notification template';

        if ($events !== '') {
            $label .= ' — ' . $events;
        }

        if ($template->merchant_id === null) {
            $label .= ' (Global)';
        }

        if (! $template->is_active) {
            $label .= ' (Inactive)';
        }

        return $label;
    }
}
