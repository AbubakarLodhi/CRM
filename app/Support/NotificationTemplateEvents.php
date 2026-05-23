<?php

namespace App\Support;

use Illuminate\Support\Str;

class NotificationTemplateEvents
{
    /**
     * Built-in system events (used by automated emails / reminders).
     *
     * @return array<string, string>
     */
    public static function builtinOptions(): array
    {
        return [
            'sale_created' => 'Sale Created',
            'purchase_created' => 'Purchase Created',
            'payment_received' => 'Payment Received',
            'credit_payment_reminder' => 'Credit Payment Reminder',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::builtinOptions();
    }

    public static function label(string $event): string
    {
        return self::builtinOptions()[$event]
            ?? Str::of($event)->replace('_', ' ')->title()->toString();
    }

    public static function slugFromLabel(string $label): string
    {
        return Str::slug(trim($label), '_');
    }

    public static function sanitizeEvent(string $event): ?string
    {
        $slug = self::slugFromLabel($event);

        if ($slug === '') {
            return null;
        }

        if (! preg_match('/^[a-z][a-z0-9_]*$/', $slug)) {
            return null;
        }

        return $slug;
    }

    /**
     * @param  array<int, string>|string|null  $events
     * @return list<string>
     */
    public static function normalize(array|string|null $events): array
    {
        if (is_string($events) && $events !== '') {
            $events = [$events];
        }

        if (! is_array($events)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $event) => is_string($event) ? self::sanitizeEvent($event) : null,
            $events
        ))));
    }

    /**
     * @param  array<int, string>|null  $values
     * @return array<string, string>
     */
    public static function labelsForValues(?array $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->mapWithKeys(fn (string $value) => [$value => self::label($value)])
            ->all();
    }
}
