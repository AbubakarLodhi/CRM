<?php

namespace App\Filament\Resources\NotificationTemplates\Schemas;

use App\Support\NotificationTemplateChannels;
use App\Support\NotificationTemplateEvents;
use Filament\Forms\Components\CodeEditor;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Forms\Components\CodeEditor\Enums\Language;

class NotificationTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Template Configuration')
                ->columnSpanFull()
                ->columns(2)
                ->schema([

                    Select::make('events')
                        ->label('Events')
                        ->options(NotificationTemplateEvents::options())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->minItems(1)
                        ->createOptionForm([
                            TextInput::make('label')
                                ->label('Custom event name')
                                ->required()
                                ->maxLength(80)
                                ->placeholder('e.g. Invoice overdue notice')
                                ->helperText('Saved as a unique event key (e.g. invoice_overdue_notice).'),
                        ])
                        ->createOptionModalHeading('Create custom event')
                        ->createOptionUsing(function (array $data): string {
                            $slug = NotificationTemplateEvents::slugFromLabel($data['label'] ?? '');

                            if ($slug === '' || NotificationTemplateEvents::sanitizeEvent($slug) !== $slug) {
                                throw ValidationException::withMessages([
                                    'label' => 'Enter a valid event name using letters and numbers.',
                                ]);
                            }

                            return $slug;
                        })
                        ->getOptionLabelsUsing(fn (array $values): array => NotificationTemplateEvents::labelsForValues($values))
                        ->helperText('Pick built-in events or click “Create” to add a custom event for this template.')
                        ->columnSpanFull()
                        ->live(),

                    Select::make('channels')
                        ->label('Channels')
                        ->options(NotificationTemplateChannels::options())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->minItems(1)
                        ->getOptionLabelsUsing(fn (array $values): array => NotificationTemplateChannels::labelsForValues($values))
                        ->helperText('Select one or more channels. When Email and WhatsApp are both selected, the same template content and variables are sent on both channels.')
                        ->columnSpanFull()
                        ->live(),

                    TextInput::make('subject')
                        ->columnSpanFull()
                        ->visible(fn ($get) => NotificationTemplateChannels::includesEmail($get('channels') ?? []))
                        ->required(fn ($get) => NotificationTemplateChannels::includesEmail($get('channels') ?? [])),

                    Toggle::make('is_active')
                        ->label('Default')
                        ->default(true),
                ]),

            Section::make('Template Builder')
                ->columnSpanFull()
                ->schema([

                    Hidden::make('preview_key')
                        ->default(fn () => (string) str()->uuid())
                        ->dehydrated(false),

                    Tabs::make('Editor')
                        ->persistTabInQueryString()
                        ->tabs([

                            Tabs\Tab::make('Code')
                                ->schema([
                                    CodeEditor::make('content')
                                        ->language(Language::Html)
                                        ->required()
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function ($set, $livewire): void {
                                            $set('preview_key', (string) str()->uuid());

                                            if (method_exists($livewire, 'resetValidation')) {
                                                $livewire->resetValidation('data.content');
                                            }
                                        })
                                        ->helperText('Use Blade variables like {{ $customer_name }}')
                                        ->extraAttributes([
                                            'style' => 'min-height: 520px;',
                                        ]),
                                ]),

                            Tabs\Tab::make('Test Data')
                                ->schema([
                                    CodeEditor::make('meta.test_payload')
                                        ->language(Language::Json)
                                        ->default('{}')
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function ($set, $livewire): void {
                                            $set('preview_key', (string) str()->uuid());

                                            if (method_exists($livewire, 'resetValidation')) {
                                                $livewire->resetValidation('data.meta.test_payload');
                                            }
                                        })
                                        ->helperText('JSON used only for preview')
                                        ->extraAttributes([
                                            'style' => 'min-height: 520px;',
                                        ]),
                                ]),

                            Tabs\Tab::make('Preview')
                                ->schema([
                                    ViewField::make('preview')
                                        ->view('filament.pages.preview')
                                        ->viewData(fn ($get) => [
                                            'template'   => $get('content'),
                                            'data'       => $get('meta.test_payload'),
                                            'previewKey' => $get('preview_key'),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }
}
