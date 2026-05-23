<?php

namespace App\Support;

use App\Enums\CreditReminderScheduleType;
use App\Enums\ReminderPeriodType;
use App\Services\CreditReminderScheduler;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class CreditReminderTemplatesForm
{
    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        return [
            Section::make('Credit payment reminders')
                ->description('Each rule is checked daily against unpaid credit invoices. Email sends only when the invoice due date exactly matches the rule (e.g. Before 1 day → due tomorrow).')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('reminders_enabled')
                        ->label('Enable payment reminders')
                        ->live()
                        ->default(false)
                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                            if ($state && empty($get('reminder_templates'))) {
                                $set('reminder_templates', [CreditReminderScheduler::defaultTemplateRow()]);
                            }
                        }),

                    Repeater::make('reminder_templates')
                        ->label('Reminder templates')
                        ->visible(fn (callable $get) => (bool) ($get('reminders_enabled') ?? false))
                        ->schema([
                            Hidden::make('credit_reminder_template_id'),

                            TextInput::make('name')
                                ->label('Reminder name')
                                ->required()
                                ->maxLength(120)
                                ->columnSpanFull(),

                            Select::make('notification_template_id')
                                ->label('Notification template')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpanFull()
                                ->options(fn (): array => CreditReminderNotificationTemplateOptions::forMerchant())
                                ->getOptionLabelUsing(fn (?string $value): ?string => CreditReminderNotificationTemplateOptions::labelFor($value))
                                ->helperText('Use Before (green), On (orange), or After (red) professional templates.'),

                            Toggle::make('is_enabled')
                                ->label('Enabled')
                                ->default(true)
                                ->inline(false)
                                ->columnSpanFull()
                                ->helperText('Disabled templates are not applied to any invoice.'),

                            Select::make('schedule_type')
                                ->label('When to remind')
                                ->options(CreditReminderScheduleType::options())
                                ->required()
                                ->live()
                                ->columnSpanFull(),

                            Select::make('offset_value')
                                ->label(fn (callable $get) => $get('schedule_type') === CreditReminderScheduleType::AfterDueDate->value
                                    ? 'Days after due date'
                                    : 'Days before due date')
                                ->options(CreditReminderDueDateMatcher::dayOffsetOptions())
                                ->default(1)
                                ->visible(fn (callable $get) => in_array(
                                    $get('schedule_type'),
                                    [
                                        CreditReminderScheduleType::BeforeDueDate->value,
                                        CreditReminderScheduleType::AfterDueDate->value,
                                    ],
                                    true
                                ))
                                ->required(fn (callable $get) => in_array(
                                    $get('schedule_type'),
                                    [
                                        CreditReminderScheduleType::BeforeDueDate->value,
                                        CreditReminderScheduleType::AfterDueDate->value,
                                    ],
                                    true
                                ))
                                ->helperText(fn (callable $get) => match ($get('schedule_type')) {
                                    CreditReminderScheduleType::BeforeDueDate->value => 'Sends only when (due date − today) equals this number. Example: 1 = invoice due tomorrow.',
                                    CreditReminderScheduleType::AfterDueDate->value => 'Sends only when (today − due date) equals this number. Example: 2 = invoice is 2 days overdue.',
                                    default => null,
                                }),

                            Hidden::make('offset_type')
                                ->default(ReminderPeriodType::Days->value),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Add reminder template')
                        ->addable(true)
                        ->deletable(true)
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => filled($state['name'] ?? null)
                            ? (string) $state['name']
                            : 'New reminder template'),
                ]),
        ];
    }
}
