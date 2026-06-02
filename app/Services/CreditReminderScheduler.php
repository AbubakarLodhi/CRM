<?php

namespace App\Services;

use App\Enums\CreditReminderScheduleType;
use App\Enums\ReminderPeriodType;
use App\Support\CreditReminderDueDateMatcher;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\WhatsApp\WhatsAppService;
use App\Support\NotificationTemplateChannels;
use App\Models\CreditReminder;
use App\Models\CreditReminderTemplate;
use App\Models\MerchantCreditReminderSetting;
use App\Models\NotificationTemplate;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CreditReminderScheduler
{
    /**
     * @param  array<string, mixed>  $formData
     */
    public function syncFromReminderTemplatesPage(string $merchantId, array $formData): void
    {
        $setting = $this->ensureMerchantSetting($merchantId);
        $masterEnabled = (bool) ($formData['reminders_enabled'] ?? false);

        $setting->update(['is_enabled' => $masterEnabled]);

        $keptTemplateIds = [];

        if ($masterEnabled) {
            foreach ($formData['reminder_templates'] ?? [] as $row) {
                $template = $this->upsertReminderTemplate($merchantId, $row);

                if ($template) {
                    $keptTemplateIds[] = $template->id;
                }
            }
        }

        CreditReminderTemplate::query()
            ->where('merchant_id', $merchantId)
            ->when($keptTemplateIds !== [], fn ($q) => $q->whereNotIn('id', $keptTemplateIds))
            ->each(function (CreditReminderTemplate $template): void {
                $this->deactivateRemindersForTemplate($template);
                $template->delete();
            });

        if ($masterEnabled) {
            $this->syncAllCreditSalesForMerchant($merchantId);
        } else {
            $this->deactivateAllRemindersForMerchant($merchantId);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function upsertReminderTemplate(string $merchantId, array $row): ?CreditReminderTemplate
    {
        $name = trim((string) ($row['name'] ?? ''));
        $templateId = $row['notification_template_id'] ?? null;
        $scheduleType = $row['schedule_type'] ?? null;

        if ($name === '' || ! filled($templateId) || ! filled($scheduleType)) {
            return null;
        }

        $schedule = CreditReminderScheduleType::tryFrom($scheduleType);

        if (! $schedule) {
            return null;
        }

        $attributes = [
            'merchant_id' => $merchantId,
            'name' => $name,
            'notification_template_id' => $templateId,
            'schedule_type' => $schedule->value,
            'is_enabled' => (bool) ($row['is_enabled'] ?? true),
            'offset_type' => null,
            'offset_value' => null,
            'repeat_type' => null,
            'repeat_value' => null,
        ];

        if (in_array($schedule, [CreditReminderScheduleType::BeforeDueDate, CreditReminderScheduleType::AfterDueDate], true)) {
            $attributes['offset_type'] = ReminderPeriodType::Days->value;
            $offset = (int) ($row['offset_value'] ?? 1);
            $attributes['offset_value'] = in_array($offset, CreditReminderDueDateMatcher::DAY_OFFSET_OPTIONS, true)
                ? $offset
                : 1;
        }

        if ($schedule === CreditReminderScheduleType::Recurring) {
            $attributes['repeat_type'] = $row['repeat_type'] ?? ReminderPeriodType::Days->value;
            $attributes['repeat_value'] = max(1, (int) ($row['repeat_value'] ?? 1));
        }

        $template = isset($row['credit_reminder_template_id'])
            ? CreditReminderTemplate::query()
                ->where('merchant_id', $merchantId)
                ->find($row['credit_reminder_template_id'])
            : null;

        if ($template) {
            $template->update($attributes);
        } else {
            $template = CreditReminderTemplate::create(array_merge($attributes, [
                'id' => (string) Str::uuid(),
            ]));
        }

        if (! $template->is_enabled) {
            $this->deactivateRemindersForTemplate($template);
        }

        return $template;
    }

    public function syncSaleReminders(Sale $sale): void
    {
        $sale->refresh();

        if (! $this->isEnabledForMerchant($sale->merchant_id) || ! $sale->isCreditWithBalance()) {
            $this->deactivateSaleReminders($sale);

            return;
        }

        $templates = CreditReminderTemplate::query()
            ->where('merchant_id', $sale->merchant_id)
            ->where('is_enabled', true)
            ->get();

        $keptIds = [];

        foreach ($templates as $template) {
            $reminder = CreditReminder::query()->firstOrNew([
                'sale_id' => $sale->id,
                'credit_reminder_template_id' => $template->id,
            ]);

            if (! $reminder->exists) {
                $reminder->id = (string) Str::uuid();
            }

            $this->applyTemplateToReminder($reminder, $sale, $template);
            $this->refreshNextSendAt($reminder);
            $keptIds[] = $reminder->id;
        }

        CreditReminder::query()
            ->where('sale_id', $sale->id)
            ->when($keptIds !== [], fn ($q) => $q->whereNotIn('id', $keptIds))
            ->update(['is_active' => false, 'next_send_at' => null]);
    }

    public function syncAllCreditSalesForMerchant(string $merchantId): void
    {
        if (! $this->isEnabledForMerchant($merchantId)) {
            $this->deactivateAllRemindersForMerchant($merchantId);

            return;
        }

        Sale::query()
            ->where('merchant_id', $merchantId)
            ->where('payment_type', 'credit')
            ->where('due_amount', '>', 0)
            ->each(fn (Sale $sale) => $this->syncSaleReminders($sale));

        CreditReminder::query()
            ->whereHas('sale', fn ($q) => $q->where('merchant_id', $merchantId))
            ->where(function ($q) {
                $q->whereNull('credit_reminder_template_id')
                    ->orWhereHas('template', fn ($t) => $t->where('is_enabled', false));
            })
            ->update(['is_active' => false, 'next_send_at' => null]);
    }

    public function applyTemplateToReminder(
        CreditReminder $reminder,
        Sale $sale,
        CreditReminderTemplate $template,
    ): void {
        $remindAt = $this->calculateRemindAt($sale, $template);

        $reminder->fill([
            'notification_template_id' => $template->notification_template_id,
            'remind_at' => $remindAt?->toDateString(),
            'repeat_type' => $template->isRecurringSchedule() ? $template->repeat_type : null,
            'repeat_value' => $template->isRecurringSchedule() ? $template->repeat_value : null,
            'is_active' => $template->is_enabled && $remindAt !== null,
        ]);

        $reminder->save();
    }

    public function calculateRemindAt(Sale $sale, CreditReminderTemplate $template): ?Carbon
    {
        return CreditReminderDueDateMatcher::calculateRemindAt($sale, $template);
    }

    public function refreshNextSendAt(CreditReminder $reminder): void
    {
        $reminder->loadMissing(['sale', 'template']);

        $sale = $reminder->sale;
        $template = $reminder->template;

        if (
            ! $sale?->isCreditWithBalance()
            || ! $reminder->remind_at
            || ! $template?->is_enabled
            || ! $this->isEnabledForMerchant($sale->merchant_id)
            || ! CreditReminderDueDateMatcher::resolveDueDate($sale)
        ) {
            $reminder->update([
                'is_active' => false,
                'next_send_at' => null,
            ]);

            return;
        }

        $reminder->update([
            'next_send_at' => $reminder->remind_at->copy()->startOfDay(),
            'is_active' => true,
        ]);
    }

    public function isEnabledForMerchant(string $merchantId): bool
    {
        return (bool) MerchantCreditReminderSetting::query()
            ->where('merchant_id', $merchantId)
            ->value('is_enabled');
    }

    public function ensureMerchantSetting(string $merchantId): MerchantCreditReminderSetting
    {
        return MerchantCreditReminderSetting::firstOrCreate(
            ['merchant_id' => $merchantId],
            [
                'id' => (string) Str::uuid(),
                'is_enabled' => false,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function loadReminderTemplatesFormState(string $merchantId): array
    {
        $setting = $this->ensureMerchantSetting($merchantId);

        $templates = CreditReminderTemplate::query()
            ->where('merchant_id', $merchantId)
            ->orderBy('name')
            ->get()
            ->map(fn (CreditReminderTemplate $template) => self::templateRowFromModel($template))
            ->values()
            ->all();

        if ($setting->is_enabled && $templates === []) {
            $templates = [self::defaultTemplateRow()];
        }

        return [
            'reminders_enabled' => (bool) $setting->is_enabled,
            'reminder_templates' => $templates,
        ];
    }

    /**
     * @return array{
     *     sent: int,
     *     failed: int,
     *     waiting: int,
     *     skipped: int,
     *     details: list<string>,
     *     disabled: bool
     * }
     */
    public function processDueReminders(): array
    {
        $sent = 0;
        $failed = 0;
        $waiting = 0;
        $skipped = 0;
        $details = [];

        $enabledMerchantIds = MerchantCreditReminderSetting::query()
            ->where('is_enabled', true)
            ->pluck('merchant_id');

        if ($enabledMerchantIds->isEmpty()) {
            return [
                'sent' => 0,
                'failed' => 0,
                'waiting' => 0,
                'skipped' => 0,
                'details' => ['No merchants have payment reminders enabled.'],
                'disabled' => true,
            ];
        }

        foreach ($enabledMerchantIds as $merchantId) {
            $result = $this->pushDueRemindersForMerchant((string) $merchantId);
            $sent += $result['sent'];
            $failed += $result['failed'];
            $waiting += $result['waiting'];
            $skipped += $result['skipped'] ?? 0;
            $details = array_merge($details, $result['details']);
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'waiting' => $waiting,
            'skipped' => $skipped,
            'details' => $details,
            'disabled' => false,
        ];
    }

    /**
     * Re-sync schedules, then run the same send logic as the daily job (due on the remind date at 12:00 AM).
     * Use for manual testing of reminder date rules.
     *
     * @return array{
     *     sent: int,
     *     failed: int,
     *     waiting: int,
     *     skipped: int,
     *     details: list<string>,
     *     disabled: bool
     * }
     */
    public function pushDueRemindersForMerchant(string $merchantId): array
    {
        if (! $this->isEnabledForMerchant($merchantId)) {
            return [
                'sent' => 0,
                'failed' => 0,
                'waiting' => 0,
                'skipped' => 0,
                'details' => ['Enable payment reminders before pushing.'],
                'disabled' => true,
            ];
        }

        $this->syncAllCreditSalesForMerchant($merchantId);

        $sent = 0;
        $failed = 0;
        $waiting = 0;
        $skipped = 0;
        $details = [];

        $reminders = CreditReminder::query()
            ->with(['sale', 'template'])
            ->where('is_active', true)
            ->whereHas('sale', fn ($q) => $q
                ->where('merchant_id', $merchantId)
                ->where('payment_type', 'credit')
                ->where('due_amount', '>', 0)
                ->whereNotNull('due_date'))
            ->whereHas('template', fn ($q) => $q->where('is_enabled', true))
            ->orderBy('remind_at')
            ->get();

        foreach ($reminders as $reminder) {
            $sale = $reminder->sale;
            $template = $reminder->template;
            $saleNo = $sale?->sale_no ?? 'Invoice';
            $templateName = $template?->name ?? 'Reminder';
            $dueDateLabel = $sale?->due_date?->format('d/m/Y') ?? '—';
            $remindAt = $reminder->remind_at?->format('d/m/Y') ?? '—';

            if (! $sale || ! $template) {
                continue;
            }

            if ($reminder->last_sent_at?->isToday()) {
                $skipped++;
                $details[] = "SKIPPED — {$saleNo} / {$templateName}: already sent today";

                continue;
            }

            if (! CreditReminderDueDateMatcher::matchesToday($sale, $template)) {
                $skipped++;
                $details[] = 'SKIPPED — ' . $templateName . ' / ' . CreditReminderDueDateMatcher::describeMatch($sale, $template);

                continue;
            }

            $report = $this->sendReminder($reminder->fresh());

            if ($report['success']) {
                $sent++;
            } else {
                $failed++;
            }

            $details[] = $this->formatReminderReportLine($report, $remindAt);
        }

        if ($reminders->isEmpty()) {
            $details[] = 'No active credit reminders. Save templates and ensure open credit sales with due dates exist.';
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'waiting' => $waiting,
            'skipped' => $skipped,
            'details' => $details,
            'disabled' => false,
        ];
    }

    /**
     * @return array{
     *     success: bool,
     *     sale_no: string,
     *     template_name: string,
     *     reason: string|null,
     *     customer_email: string|null,
     *     admin_email: string|null,
     *     recipients: list<string>,
     *     skipped?: list<string>
     * }
     */
    public function sendReminder(CreditReminder $reminder): array
    {
        $reminder->loadMissing([
            'sale.customer',
            'sale.merchant.logo',
            'sale.merchant.settings',
            'sale.items.product',
            'sale.payments',
            'notificationTemplate',
            'template',
        ]);

        $sale = $reminder->sale;
        $template = $reminder->template;
        $saleNo = $sale?->sale_no ?? 'Invoice';
        $templateName = $template?->name ?? 'Reminder';

        $report = [
            'success' => false,
            'sale_no' => $saleNo,
            'template_name' => $templateName,
            'reason' => null,
            'customer_email' => $sale?->customer?->email,
            'admin_email' => $sale?->merchant?->email,
            'recipients' => [],
            'skipped' => [],
        ];

        if (
            ! $sale
            || ! $sale->isCreditWithBalance()
            || ! $template?->is_enabled
            || ! $this->isEnabledForMerchant($sale->merchant_id)
        ) {
            $reminder->update(['is_active' => false, 'next_send_at' => null]);
            $report['reason'] = 'Sale is not an open credit invoice or reminder template is disabled';

            return $report;
        }

        if (! CreditReminderDueDateMatcher::matchesToday($sale, $template)) {
            $report['reason'] = 'Date rule not matched today: ' . CreditReminderDueDateMatcher::describeMatch($sale, $template);

            return $report;
        }

        if ($reminder->last_sent_at?->isToday()) {
            $report['reason'] = 'Already sent today for this reminder rule';

            return $report;
        }

        $notificationTemplate = $reminder->notificationTemplate
            ?? $this->resolveCreditReminderTemplate($sale);

        if (! $notificationTemplate) {
            $report['reason'] = 'No notification template found for credit_payment_reminder';
            Log::warning('Credit reminder skipped: no notification template', ['sale_id' => $sale->id]);

            return $report;
        }

        $customerEmail = $sale->customer?->email;
        $adminEmail = $sale->merchant?->email;
        $customerPhone = $sale->customer?->phone;
        $adminPhone = $sale->merchant?->whatsapp_number ?: $sale->merchant?->phone;

        $channels = NotificationTemplateChannels::normalize($notificationTemplate->channels);
        $needsEmail = in_array('email', $channels, true);
        $needsWhatsApp = in_array('whatsapp', $channels, true) && app(WhatsAppService::class)->isEnabled();

        if ($needsEmail && ! $customerEmail && ! $adminEmail) {
            $report['reason'] = 'No customer or merchant email on file';
            Log::warning('Credit reminder skipped: no recipient emails', ['sale_id' => $sale->id]);

            return $report;
        }

        if ($needsWhatsApp && ! $customerPhone && ! $adminPhone) {
            $report['reason'] = 'No customer or merchant phone on file for WhatsApp';
            Log::warning('Credit reminder skipped: no recipient phones', ['sale_id' => $sale->id]);

            return $report;
        }

        try {
            $dispatcher = app(NotificationDispatcher::class);
            $allSent = [];
            $allSkipped = [];

            if ($customerEmail || $customerPhone) {
                $customerResult = $dispatcher->dispatchCreditReminder(
                    $sale,
                    $reminder,
                    $notificationTemplate,
                    'customer',
                    $customerEmail,
                    $customerPhone,
                );
                $allSent = array_merge($allSent, $customerResult->sent);
                $allSkipped = array_merge($allSkipped, $customerResult->skipped);
            }

            if ($adminEmail || $adminPhone) {
                $adminResult = $dispatcher->dispatchCreditReminder(
                    $sale,
                    $reminder,
                    $notificationTemplate,
                    'admin',
                    $adminEmail,
                    $adminPhone,
                );
                $allSent = array_merge($allSent, $adminResult->sent);
                $allSkipped = array_merge($allSkipped, $adminResult->skipped);
            }

            if ($allSent === []) {
                $report['reason'] = 'No messages sent: ' . implode('; ', $allSkipped);

                return $report;
            }

            $report['recipients'] = $allSent;
            $report['skipped'] = $allSkipped;
            $now = now();

            $reminder->update([
                'last_sent_at' => $now,
                'next_send_at' => null,
                'is_active' => false,
            ]);

            $report['success'] = true;
            $report['reason'] = 'Sent: ' . implode(', ', $allSent);
            if ($allSkipped !== []) {
                $report['reason'] .= ' | Skipped: ' . implode(', ', $allSkipped);
            }

            return $report;
        } catch (Throwable $exception) {
            $report['reason'] = 'Error: ' . $exception->getMessage();
            Log::error('Credit sale reminder failed', [
                'sale_id' => $sale->id,
                'reminder_id' => $reminder->id,
                'error' => $exception->getMessage(),
            ]);

            return $report;
        }
    }

    public function formatReminderReportLine(array $report, ?string $dueOn = null): string
    {
        if ($report['success']) {
            $delivered = $report['recipients'] !== []
                ? implode(', ', $report['recipients'])
                : 'no recipients';

            $line = "SENT — {$report['sale_no']} / {$report['template_name']}"
                . ($dueOn ? " (due {$dueOn})" : '')
                . " → {$delivered}";

            if (filled($report['skipped'] ?? null)) {
                $line .= ' | WhatsApp/email skipped: ' . implode(', ', $report['skipped']);
            }

            return $line;
        }

        $emails = collect([
            $report['customer_email'] ? "customer: {$report['customer_email']}" : null,
            $report['admin_email'] ? "admin: {$report['admin_email']}" : null,
        ])->filter()->implode(', ');

        $emailNote = $emails !== '' ? " [emails on file: {$emails}]" : ' [no emails on file]';

        return "NOT SENT — {$report['sale_no']} / {$report['template_name']}: "
            . ($report['reason'] ?? 'unknown reason')
            . $emailNote;
    }

    public function resolveCreditReminderTemplate(Sale $sale): ?NotificationTemplate
    {
        $baseQuery = fn () => NotificationTemplate::query()
            ->where('is_active', true)
            ->where(function ($query) use ($sale) {
                $query->where('merchant_id', $sale->merchant_id)
                    ->orWhereNull('merchant_id');
            });

        return $baseQuery()
            ->forEvent('credit_payment_reminder')
            ->orderByRaw('merchant_id is null')
            ->latest('updated_at')
            ->first()
            ?? $baseQuery()
                ->orderByRaw('merchant_id is null')
                ->latest('updated_at')
                ->first();
    }

    public function deactivateSaleReminders(Sale $sale): void
    {
        CreditReminder::query()
            ->where('sale_id', $sale->id)
            ->update(['is_active' => false, 'next_send_at' => null]);
    }

    public function deactivateRemindersForTemplate(CreditReminderTemplate $template): void
    {
        CreditReminder::query()
            ->where('credit_reminder_template_id', $template->id)
            ->update(['is_active' => false, 'next_send_at' => null]);
    }

    public function deactivateAllRemindersForMerchant(string $merchantId): void
    {
        CreditReminder::query()
            ->whereHas('sale', fn ($q) => $q->where('merchant_id', $merchantId))
            ->update(['is_active' => false, 'next_send_at' => null]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultTemplateRow(): array
    {
        return [
            'credit_reminder_template_id' => null,
            'name' => null,
            'notification_template_id' => null,
            'schedule_type' => CreditReminderScheduleType::OnDueDate->value,
            'offset_type' => ReminderPeriodType::Days->value,
            'offset_value' => 1,
            'repeat_type' => null,
            'repeat_value' => null,
            'is_enabled' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function templateRowFromModel(CreditReminderTemplate $template): array
    {
        return [
            'credit_reminder_template_id' => $template->id,
            'name' => $template->name,
            'notification_template_id' => $template->notification_template_id,
            'schedule_type' => $template->schedule_type,
            'offset_type' => $template->offset_type ?? ReminderPeriodType::Days->value,
            'offset_value' => $template->offset_value ?? 3,
            'repeat_type' => $template->repeat_type ?? ReminderPeriodType::Days->value,
            'repeat_value' => $template->repeat_value ?? 7,
            'is_enabled' => $template->is_enabled,
        ];
    }

}
