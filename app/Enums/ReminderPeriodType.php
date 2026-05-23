<?php

namespace App\Enums;

enum ReminderPeriodType: string
{
    case Months = 'months';
    case Weeks = 'weeks';
    case Days = 'days';

    public function label(): string
    {
        return match ($this) {
            self::Months => 'Months',
            self::Weeks => 'Weeks',
            self::Days => 'Days',
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
