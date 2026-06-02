<?php

namespace Tests\Unit;

use App\Enums\CreditReminderScheduleType;
use App\Support\CreditReminderEmailCaption;
use PHPUnit\Framework\TestCase;

class CreditReminderEmailCaptionTest extends TestCase
{
    public function test_before_due_date_customer_caption(): void
    {
        $caption = CreditReminderEmailCaption::forSchedule(CreditReminderScheduleType::BeforeDueDate, 'customer');

        $this->assertStringContainsString('due soon', $caption);
        $this->assertStringContainsString('attached PDF', $caption);
    }

    public function test_on_due_date_customer_caption(): void
    {
        $caption = CreditReminderEmailCaption::forSchedule(CreditReminderScheduleType::OnDueDate, 'customer');

        $this->assertStringContainsString('due today', $caption);
    }

    public function test_after_due_date_customer_caption(): void
    {
        $caption = CreditReminderEmailCaption::forSchedule(CreditReminderScheduleType::AfterDueDate, 'customer');

        $this->assertStringContainsString('overdue', $caption);
    }

    public function test_email_body_includes_pdf_footer_line(): void
    {
        $body = CreditReminderEmailCaption::emailBody([
            'schedule_type' => CreditReminderScheduleType::OnDueDate->value,
        ], 'customer');

        $this->assertStringContainsString(CreditReminderEmailCaption::PDF_FOOTER_LINE, $body);
    }
}
