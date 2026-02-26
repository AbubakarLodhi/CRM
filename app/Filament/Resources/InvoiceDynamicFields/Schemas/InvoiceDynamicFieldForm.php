<?php

namespace App\Filament\Resources\InvoiceDynamicFields\Schemas;

use App\Filament\Resources\InvoiceDynamicFields\InvoiceDynamicFieldResource;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\Str;

class InvoiceDynamicFieldForm
{
    /**
     * @return array<string, string>
     */
    protected static function valueTypeToTableMap(): array
    {
        return [
            'merchant' => 'merchants',
            'business' => 'businesses',
            'business_logo' => 'businesses',
            'branch' => 'branches',
            'customer' => 'customers',
            'vendor' => 'vendors',
            'sale' => 'sales',
            'purchase' => 'purchases',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function valueKeyOptions(?string $valueType): array
    {
        $table = self::valueTypeToTableMap()[$valueType ?? ''] ?? null;

        if (! $table || ! SchemaFacade::hasTable($table)) {
            return [];
        }

        $excluded = ['id', 'password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];

        return collect(SchemaFacade::getColumnListing($table))
            ->reject(fn (string $column) => in_array($column, $excluded, true))
            ->mapWithKeys(fn (string $column) => [$column => Str::of($column)->replace('_', ' ')->title()->toString()])
            ->toArray();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice Template')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('section')
                        ->label('Area')
                        ->options([
                            'header' => 'Header',
                            'footer' => 'Footer',
                        ])
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $livewire): void {
                            if (method_exists($livewire, 'resetValidation')) {
                                $livewire->resetValidation('data.section');
                            }
                        }),

                    TextInput::make('name')
                        ->label('Group Name')
                        ->placeholder('e.g. Company Meta, Bank Details')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $livewire): void {
                            if (method_exists($livewire, 'resetValidation')) {
                                $livewire->resetValidation('data.name');
                            }
                        }),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Hidden::make('merchant_id')
                        ->default(fn () => InvoiceDynamicFieldResource::resolveMerchantId())
                        ->required(),
                ]),

            Section::make('Template Fields')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('fields')
                        ->relationship('fields')
                        ->defaultItems(1)
                        ->addActionLabel('Add Another Field')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                        ->schema([
                            TextInput::make('label')
                                ->label('Field Label')
                                ->required()
                                ->maxLength(120)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $livewire): void {
                                    if (method_exists($livewire, 'resetValidation')) {
                                        $livewire->resetValidation('data.fields.*.label');
                                    }
                                }),

                            Select::make('value_type')
                                ->options([
                                    'static' => 'Static Text',
                                    'merchant' => 'Merchant',
                                    'business' => 'Business',
                                    'business_logo' => 'Business Logo',
                                    'branch' => 'Branch',
                                    'customer' => 'Customer (Sales)',
                                    'vendor' => 'Vendor (Purchases)',
                                    'sale' => 'Sale Record',
                                    'purchase' => 'Purchase Record',
                                ])
                                ->default('static')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $livewire): void {
                                    if (method_exists($livewire, 'resetValidation')) {
                                        $livewire->resetValidation('data.fields.*.value_type');
                                        $livewire->resetValidation('data.fields.*.value_key');
                                        $livewire->resetValidation('data.fields.*.static_value');
                                    }
                                }),

                            Select::make('value_key')
                                ->label('Value Key')
                                ->options(fn ($get): array => self::valueKeyOptions((string) $get('value_type')))
                                ->searchable()
                                ->preload()
                                ->visible(fn ($get): bool => ! in_array($get('value_type'), ['static', 'business_logo'], true))
                                ->required(fn ($get): bool => ! in_array($get('value_type'), ['static', 'business_logo'], true))
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $livewire): void {
                                    if (method_exists($livewire, 'resetValidation')) {
                                        $livewire->resetValidation('data.fields.*.value_key');
                                    }
                                }),

                            TextInput::make('static_value')
                                ->maxLength(255)
                                ->visible(fn ($get): bool => $get('value_type') === 'static')
                                ->required(fn ($get): bool => $get('value_type') === 'static')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $livewire): void {
                                    if (method_exists($livewire, 'resetValidation')) {
                                        $livewire->resetValidation('data.fields.*.static_value');
                                    }
                                }),

                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $livewire): void {
                                    if (method_exists($livewire, 'resetValidation')) {
                                        $livewire->resetValidation('data.fields.*.sort_order');
                                    }
                                }),

                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ])
                        ->minItems(1),
                ]),
        ]);
    }
}
