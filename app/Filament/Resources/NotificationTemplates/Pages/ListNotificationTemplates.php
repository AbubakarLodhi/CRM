<?php

namespace App\Filament\Resources\NotificationTemplates\Pages;

use App\Enums\CreditReminderScheduleType;
use App\Filament\Resources\NotificationTemplates\NotificationTemplateResource;
use App\Services\CreditReminderScheduler;
use App\Support\CreditReminderMerchantContext;
use App\Support\CreditReminderTemplatesForm;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListNotificationTemplates extends ListRecords
{
    protected static string $resource = NotificationTemplateResource::class;

    protected string $view = 'filament.pages.list-notification-templates';

    public ?array $reminderData = [];

    public function mount(): void
    {
        parent::mount();

        $merchantId = CreditReminderMerchantContext::resolveMerchantId();

        $this->reminderData = $merchantId
            ? app(CreditReminderScheduler::class)->loadReminderTemplatesFormState($merchantId)
            : [
                'reminders_enabled' => false,
                'reminder_templates' => [],
            ];
    }

    public function reminderForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('reminderData')
            ->components(CreditReminderTemplatesForm::schema());
    }

    public function saveReminders(CreditReminderScheduler $scheduler): void
    {
        $merchantId = CreditReminderMerchantContext::resolveMerchantId();

        if (! $merchantId) {
            Notification::make()->title('No merchant context')->danger()->send();

            return;
        }

        if (! $this->userCanSaveReminders()) {
            Notification::make()->title('You do not have permission to save reminders.')->danger()->send();

            return;
        }

        $this->reminderData['reminder_templates'] = collect($this->reminderData['reminder_templates'] ?? [])
            ->filter(function (array $row): bool {
                return filled($row['name'] ?? null)
                    || filled($row['notification_template_id'] ?? null)
                    || filled($row['schedule_type'] ?? null)
                    || filled($row['credit_reminder_template_id'] ?? null);
            })
            ->values()
            ->all();

        if (($this->reminderData['reminders_enabled'] ?? false) && empty($this->reminderData['reminder_templates'])) {
            Notification::make()
                ->title('Add at least one reminder template')
                ->body('Turn on reminders and add a template, or turn reminders off.')
                ->warning()
                ->send();

            return;
        }

        foreach ($this->reminderData['reminder_templates'] ?? [] as $row) {
            if (! filled($row['name'] ?? null)) {
                Notification::make()
                    ->title('Reminder name required')
                    ->body('Each reminder template needs a name.')
                    ->warning()
                    ->send();

                return;
            }

            if (! filled($row['notification_template_id'] ?? null)) {
                Notification::make()
                    ->title('Select a notification template')
                    ->body('Each reminder template needs an email notification template.')
                    ->warning()
                    ->send();

                return;
            }

            if (! filled($row['schedule_type'] ?? null)) {
                Notification::make()
                    ->title('Select when to remind')
                    ->body('Choose one schedule option per reminder template.')
                    ->warning()
                    ->send();

                return;
            }

            $schedule = CreditReminderScheduleType::tryFrom($row['schedule_type']);

            if (! $schedule) {
                Notification::make()->title('Invalid schedule option')->warning()->send();

                return;
            }

            if (in_array($schedule, [CreditReminderScheduleType::BeforeDueDate, CreditReminderScheduleType::AfterDueDate], true)) {
                if (! filled($row['offset_value'] ?? null)) {
                    Notification::make()
                        ->title('Offset required')
                        ->body('Before/after due date reminders need a period and value.')
                        ->warning()
                        ->send();

                    return;
                }
            }

            if ($schedule === CreditReminderScheduleType::Recurring) {
                if (! filled($row['repeat_value'] ?? null)) {
                    Notification::make()
                        ->title('Repeat interval required')
                        ->body('Recurring reminders need a repeat interval.')
                        ->warning()
                        ->send();

                    return;
                }
            }
        }

        try {
            $scheduler->syncFromReminderTemplatesPage($merchantId, $this->reminderData);

            $this->reminderData = $scheduler->loadReminderTemplatesFormState($merchantId);

            Notification::make()
                ->title('Payment reminders saved')
                ->body(count($this->reminderData['reminder_templates'] ?? []) . ' reminder template(s) saved.')
                ->success()
                ->send();
        } catch (QueryException $exception) {
            Log::error('Credit reminder templates save failed', [
                'merchant_id' => $merchantId,
                'error' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Could not save reminder templates')
                ->body('Database error: ' . $exception->getMessage())
                ->danger()
                ->send();
        } catch (Throwable $exception) {
            Log::error('Credit reminder templates save failed', [
                'merchant_id' => $merchantId,
                'error' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Could not save reminder templates')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function pushCreditReminders(CreditReminderScheduler $scheduler): void
    {
        $merchantId = CreditReminderMerchantContext::resolveMerchantId();

        if (! $merchantId) {
            Notification::make()->title('No merchant context')->danger()->send();

            return;
        }

        if (! $this->userCanSaveReminders()) {
            Notification::make()->title('You do not have permission to push reminders.')->danger()->send();

            return;
        }

        $result = $scheduler->pushDueRemindersForMerchant($merchantId);

        foreach ($result['details'] as $detail) {
            Log::info('[Credit Reminder Push] ' . $detail);
        }

        if ($result['disabled']) {
            Notification::make()
                ->title('Reminders are disabled')
                ->body($result['details'][0] ?? 'Turn on payment reminders first.')
                ->warning()
                ->send();

            return;
        }

        $summary = collect([
            "{$result['sent']} sent",
            $result['waiting'] > 0 ? "{$result['waiting']} waiting (sends on remind date at 12:00 AM)" : null,
            $result['failed'] > 0 ? "{$result['failed']} not sent" : null,
        ])->filter()->implode(' · ');

        $body = collect($result['details'])->take(8)->implode("\n");

        if (count($result['details']) > 8) {
            $body .= "\n… and " . (count($result['details']) - 8) . ' more';
        }

        $notification = Notification::make()
            ->title($result['sent'] > 0 ? 'Reminders pushed' : 'No reminders due right now')
            ->body(trim($summary . ($body !== '' ? "\n\n" . $body : '')));

        if ($result['sent'] > 0) {
            $notification->success();
        } elseif ($result['waiting'] > 0) {
            $notification->warning();
        } else {
            $notification->info();
        }

        $notification->send();
    }

    public function userCanSaveReminders(): bool
    {
        $user = Filament::auth()->user();

        return $user?->hasPermissionTo('notification_templates.update', Filament::getCurrentPanel()->getAuthGuard()) ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('notification_templates.create', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
}
