<?php

namespace App\Filament\Resources\NotificationTemplates\Schemas;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Forms\Components\CodeEditor\Enums\Language;

class NotificationTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            /* =========================
             * Template Configuration
             * ========================= */
            Section::make('Template Configuration')
                ->columnSpanFull()
                ->columns(2)
                ->schema([

                    Select::make('event')
                        ->options([
                            'sale_created'     => 'Sale Created',
                            'purchase_created' => 'Purchase Created',
                            'payment_received' => 'Payment Received',
                        ])
                        ->required(),

                    Select::make('channel')
                        ->options([
                            'email'    => 'Email',
                            'whatsapp' => 'WhatsApp',
                            'sms'      => 'SMS',
                        ])
                        ->required(),

                    TextInput::make('subject')
                        ->columnSpanFull()
                        ->visible(fn ($get) => $get('channel') === 'email'),

                    Toggle::make('is_active')
                        ->label('Default')
                        ->default(true),
                ]),

            /* =========================
             * Template Builder
             * ========================= */
            Section::make('Template Builder')
                ->columnSpanFull()
                ->schema([

                    /* 🔑 FORCE RE-RENDER KEY */
                    Hidden::make('preview_key')
                        ->default(fn () => (string) str()->uuid())
                        ->dehydrated(false),

                    Tabs::make('Editor')
                        ->persistTabInQueryString()
                        ->tabs([

                            /* =========================
                             * CODE TAB
                             * ========================= */
                            Tabs\Tab::make('Code')
                                ->schema([
                                    CodeEditor::make('content')
                                        ->language(Language::Html)
                                        ->required()
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(
                                            fn ($set) =>
                                            $set('preview_key', (string) str()->uuid())
                                        )
                                        ->helperText('Use Blade variables like {{ $customer_name }}')
                                        ->extraAttributes([
                                            'style' => 'min-height: 520px;',
                                        ]),
                                ]),

                            /* =========================
                             * TEST DATA TAB
                             * ========================= */
                            Tabs\Tab::make('Test Data')
                                ->schema([
                                    CodeEditor::make('meta.test_payload')
                                        ->language(Language::Json)
                                        ->default('{}')
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(
                                            fn ($set) =>
                                            $set('preview_key', (string) str()->uuid())
                                        )
                                        ->helperText('JSON used only for preview')
                                        ->extraAttributes([
                                            'style' => 'min-height: 520px;',
                                        ]),
                                ]),

                            /* =========================
                             * PREVIEW TAB
                             * ========================= */
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
