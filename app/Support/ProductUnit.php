<?php

namespace App\Support;

class ProductUnit
{
    public const DEFAULT = 'pcs';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'pcs' => 'Pieces',
            'liter' => 'Liter',
            'gram' => 'Gram',
            'kg' => 'Kilogram',
            'job' => 'Job',
            'hour' => 'Hour',
            'day' => 'Day',
            'sqm' => 'Square Meter',
            'set' => 'Set',
        ];
    }

    public static function normalize(?string $unit): string
    {
        $unit = strtolower(trim((string) $unit));

        return match ($unit) {
            '', 'piece', 'pieces' => self::DEFAULT,
            default => array_key_exists($unit, self::options()) ? $unit : self::DEFAULT,
        };
    }
}
