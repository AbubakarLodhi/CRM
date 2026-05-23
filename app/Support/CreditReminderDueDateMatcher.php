<?php

namespace App\Support;

use App\Enums\CreditReminderScheduleType;
use App\Models\CreditReminderTemplate;
use App\Models\Sale;
use Carbon\Carbon;

class CreditReminderDueDateMatcher
{
    /** @var list<int> */
    public const DAY_OFFSET_OPTIONS = [1, 2, 3, 5, 7, 10];

    public static function resolveDueDate(Sale $sale): ?Carbon
    {
        if (! $sale->due_date) {
            return null;
        }

        return $sale->due_date->copy()->startOfDay();
    }

    /**
     * Calendar days from today until due date (1 = due tomorrow, 0 = due today).
     */
    public static function daysUntilDueDate(Sale $sale): ?int
    {
        $dueDate = self::resolveDueDate($sale);

        if (! $dueDate) {
            return null;
        }

        $today = now()->startOfDay();

        if ($dueDate->lessThanOrEqualTo($today)) {
            return 0;
        }

        return (int) $today->diffInDays($dueDate);
    }

    /**
     * Calendar days overdue (0 if due date is today or in the future).
     */
    public static function daysOverdue(Sale $sale): ?int
    {
        $dueDate = self::resolveDueDate($sale);

        if (! $dueDate) {
            return null;
        }

        $today = now()->startOfDay();

        if ($dueDate->greaterThanOrEqualTo($today)) {
            return 0;
        }

        return (int) $dueDate->diffInDays($today);
    }

    public static function calculateRemindAt(Sale $sale, CreditReminderTemplate $template): ?Carbon
    {
        $dueDate = self::resolveDueDate($sale);
        $schedule = $template->scheduleType();

        if (! $dueDate || ! $schedule) {
            return null;
        }

        $offsetDays = max(1, (int) ($template->offset_value ?? 1));

        return match ($schedule) {
            CreditReminderScheduleType::OnDueDate => $dueDate->copy(),
            CreditReminderScheduleType::BeforeDueDate => $dueDate->copy()->subDays($offsetDays),
            CreditReminderScheduleType::AfterDueDate => $dueDate->copy()->addDays($offsetDays),
            CreditReminderScheduleType::Recurring => $dueDate->copy(),
        };
    }

    /**
     * Send only when today's date exactly matches the reminder rule for this invoice due date.
     */
    public static function matchesToday(Sale $sale, CreditReminderTemplate $template): bool
    {
        $dueDate = self::resolveDueDate($sale);
        $schedule = $template->scheduleType();

        if (! $dueDate || ! $schedule) {
            return false;
        }

        $offsetDays = (int) ($template->offset_value ?? 1);

        return match ($schedule) {
            CreditReminderScheduleType::OnDueDate => $dueDate->isSameDay(now()),
            CreditReminderScheduleType::BeforeDueDate => self::daysUntilDueDate($sale) === $offsetDays,
            CreditReminderScheduleType::AfterDueDate => self::daysOverdue($sale) === $offsetDays,
            CreditReminderScheduleType::Recurring => $dueDate->lessThanOrEqualTo(now()->startOfDay()),
        };
    }

    public static function describeMatch(Sale $sale, CreditReminderTemplate $template): string
    {
        $dueDate = self::resolveDueDate($sale);
        $dueLabel = $dueDate?->format('d/m/Y') ?? 'no due date';
        $schedule = $template->scheduleType();
        $offset = (int) ($template->offset_value ?? 1);
        $today = now()->format('d/m/Y');

        if (! $dueDate) {
            return "invoice {$sale->sale_no}: no due date set";
        }

        return match ($schedule) {
            CreditReminderScheduleType::OnDueDate => "invoice {$sale->sale_no}: due {$dueLabel}, today {$today} — needs due date = today",
            CreditReminderScheduleType::BeforeDueDate => sprintf(
                'invoice %s: due %s, %d day(s) until due (need exactly %d)',
                $sale->sale_no,
                $dueLabel,
                self::daysUntilDueDate($sale) ?? 0,
                $offset
            ),
            CreditReminderScheduleType::AfterDueDate => sprintf(
                'invoice %s: due %s, %d day(s) overdue (need exactly %d)',
                $sale->sale_no,
                $dueLabel,
                self::daysOverdue($sale),
                $offset
            ),
            CreditReminderScheduleType::Recurring => "invoice {$sale->sale_no}: recurring rule",
        };
    }

    /**
     * @return array<int, string>
     */
    public static function dayOffsetOptions(): array
    {
        return collect(self::DAY_OFFSET_OPTIONS)
            ->mapWithKeys(fn (int $days) => [$days => $days . ($days === 1 ? ' day' : ' days')])
            ->all();
    }
}
