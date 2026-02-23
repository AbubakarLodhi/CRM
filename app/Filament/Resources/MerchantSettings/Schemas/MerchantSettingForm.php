<?php

namespace App\Filament\Resources\MerchantSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([


            FileUpload::make('merchant_logo')
                ->label('Merchant Logo')
                ->image()
                ->disk('public')
                ->directory('merchants/logos')
                ->imagePreviewHeight(120),

//            FileUpload::make('profile_photo')
//                ->label('Profile Photo')
//                ->image()
//                ->disk('public')
//                ->directory('merchants/profile-photos')
//                ->imagePreviewHeight(120),


            /* ================= LIGHT MODE COLORS ================= */
            Section::make('Light Mode Colors')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    ColorPicker::make('primary_color')
                        ->label('Primary')
                        ->default('#1E3A8A')
                        ->required(),

                    ColorPicker::make('secondary_color')
                        ->label('Secondary')
                        ->default('#64748B')
                        ->required(),

                    ColorPicker::make('warning_color')
                        ->label('Warning')
                        ->default('#FACC15')
                        ->required(),

                    ColorPicker::make('danger_color')
                        ->label('Danger')
                        ->default('#DC2626')
                        ->required(),

                    ColorPicker::make('success_color')
                        ->label('Success')
                        ->default('#22C55E')
                        ->required(),

                    ColorPicker::make('default_color')
                        ->label('Default')
                        ->default('#E5E7EB')
                        ->required(),
                ]),

            Section::make('Cash Accounts')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('cash_in_hand')
                        ->label('Cash In Hand')
                        ->prefix('PKR')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->dehydrated(false),

                    TextInput::make('cash_in_bank')
                        ->label('Cash In Bank')
                        ->prefix('PKR')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->dehydrated(false),
                ]),

            Section::make('Invoice Dynamic Header Fields')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('invoice_header_groups')
                        ->label('Header Groups')
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['group_name'] ?? null)
                        ->addActionLabel('Add Header Group')
                        ->schema([
                            TextInput::make('group_name')
                                ->label('Group Name')
                                ->placeholder('Company Meta')
                                ->required()
                                ->maxLength(120),
                            Toggle::make('is_active')
                                ->label('Use this group on invoice')
                                ->default(false),

                            Repeater::make('fields')
                                ->label('Fields')
                                ->columns(2)
                                ->defaultItems(1)
                                ->addActionLabel('Add Field')
                                ->schema([
                                    TextInput::make('label')
                                        ->label('Field Name')
                                        ->placeholder('NTN')
                                        ->required()
                                        ->maxLength(120),
                                    TextInput::make('value')
                                        ->label('Field Value')
                                        ->placeholder('12345-6')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->required()
                                ->minItems(1),
                        ])
                        ->default([])
                        ->columns(1),
                ]),

            Section::make('Invoice Dynamic Footer Fields')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('invoice_footer_groups')
                        ->label('Footer Groups')
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['group_name'] ?? null)
                        ->addActionLabel('Add Footer Group')
                        ->schema([
                            TextInput::make('group_name')
                                ->label('Group Name')
                                ->placeholder('Bank Details')
                                ->required()
                                ->maxLength(120),
                            Toggle::make('is_active')
                                ->label('Use this group on invoice')
                                ->default(false),

                            Repeater::make('fields')
                                ->label('Fields')
                                ->columns(2)
                                ->defaultItems(1)
                                ->addActionLabel('Add Field')
                                ->schema([
                                    TextInput::make('label')
                                        ->label('Field Name')
                                        ->placeholder('IBAN')
                                        ->required()
                                        ->maxLength(120),
                                    TextInput::make('value')
                                        ->label('Field Value')
                                        ->placeholder('PK36SCBL...')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->required()
                                ->minItems(1),
                        ])
                        ->default([])
                        ->columns(1),
                ]),

            /* ================= MERCHANT ================= */
            Hidden::make('merchant_id')
                ->default(fn() => auth('merchant')->id())
                ->required(),
        ])->columns(1);
    }
}
