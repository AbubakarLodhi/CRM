<?php

namespace Tests\Unit;

use App\Enums\CreditReminderScheduleType;
use App\Enums\ReminderPeriodType;
use App\Models\CreditReminder;
use App\Models\CreditReminderTemplate;
use App\Services\CreditReminderScheduler;
use Carbon\Carbon;
use Tests\TestCase;

class CreditReminderSchedulerTest extends TestCase
{
    public function test_one_time_reminder_is_marked_already_sent_after_first_delivery(): void
    {
        $scheduler = new CreditReminderScheduler;

        $template = CreditReminderTemplate::make([
            'schedule_type' => CreditReminderScheduleType::OnDueDate->value,
        ]);

        $reminder = CreditReminder::make([
            'last_sent_at' => Carbon::parse('2026-06-01 09:00:00'),
        ]);

        $this->assertTrue($scheduler->reminderWasAlreadySent($reminder, $template));
    }

    public function test_recurring_reminder_waits_until_next_interval(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-10 09:00:00'));

        $scheduler = new CreditReminderScheduler;

        $template = CreditReminderTemplate::make([
            'schedule_type' => CreditReminderScheduleType::Recurring->value,
        ]);

        $reminder = CreditReminder::make([
            'last_sent_at' => Carbon::parse('2026-06-10 08:00:00'),
            'repeat_type' => ReminderPeriodType::Days->value,
            'repeat_value' => 7,
        ]);

        $this->assertTrue($scheduler->reminderWasAlreadySent($reminder, $template));
        $this->assertTrue(
            $scheduler->calculateNextRecurringSendAt($reminder)?->equalTo(Carbon::parse('2026-06-17')->startOfDay())
        );

        Carbon::setTestNow();
    }
}
