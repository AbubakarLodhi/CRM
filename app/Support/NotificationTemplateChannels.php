<?php

namespace App\Support;

class NotificationTemplateChannels
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'email' => 'Email',
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
        ];
    }

    public static function label(string $channel): string
    {
        return self::options()[$channel] ?? $channel;
    }

    /**
     * @param  array<int, string>|string|null  $channels
     * @return list<string>
     */
    public static function normalize(array|string|null $channels): array
    {
        if (is_string($channels) && $channels !== '') {
            $channels = [$channels];
        }

        if (! is_array($channels)) {
            return [];
        }

        $allowed = array_keys(self::options());

        return array_values(array_unique(array_filter(
            $channels,
            fn (mixed $channel) => is_string($channel) && in_array($channel, $allowed, true)
        )));
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

    public static function includesEmail(?array $channels): bool
    {
        return in_array('email', self::normalize($channels), true);
    }
}
