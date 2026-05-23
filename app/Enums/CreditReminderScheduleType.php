<?php

namespace App\Enums;

enum CreditReminderScheduleType: string
{
    case OnDueDate = 'on_due_date';
    case BeforeDueDate = 'before_due_date';
    case AfterDueDate = 'after_due_date';
    case Recurring = 'recurring';

    public function label(): string
    {
        return match ($this) {
            self::OnDueDate => 'On due date',
            self::BeforeDueDate => 'Before due date',
            self::AfterDueDate => 'After due date (if unpaid)',
            self::Recurring => 'Recurring',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect([self::BeforeDueDate, self::OnDueDate, self::AfterDueDate])
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
