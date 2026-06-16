<?php

namespace App\Enums;

enum AssetCondition: string
{
    case New = 'new';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Good => 'Good',
            self::Fair => 'Fair',
            self::Poor => 'Poor',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
