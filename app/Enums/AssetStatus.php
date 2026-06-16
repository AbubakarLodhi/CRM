<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Active = 'active';
    case InMaintenance = 'in_maintenance';
    case Disposed = 'disposed';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::InMaintenance => 'In Maintenance',
            self::Disposed => 'Disposed',
            self::Lost => 'Lost',
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
