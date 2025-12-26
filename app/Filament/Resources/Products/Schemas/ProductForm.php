<?php
//
//namespace App\Filament\Resources\Products\Schemas;
//
//use Filament\Facades\Filament;
//use Filament\Forms\Components\Hidden;
//use Filament\Forms\Components\Select;
//use Filament\Forms\Components\TextInput;
//use Filament\Forms\Components\Textarea;
//use Filament\Forms\Components\Toggle;
//use Filament\Schemas\Schema;
//
//class ProductForm
//{
//    public static function configure(Schema $schema): Schema
//    {
//        return $schema
//            ->components([
//
//                Select::make('category_id')
//                    ->relationship('category', 'name')
//                    ->searchable()
//                    ->preload()
//                    ->nullable(),
//
//                Select::make('sub_category_id')
//                    ->relationship('subCategory', 'name')
//                    ->searchable()
//                    ->preload()
//                    ->nullable(),
//                /* -------------------------
//                 | Core Info
//                 |--------------------------*/
//                TextInput::make('name')
//                    ->required()
//                    ->maxLength(255),
//
////                TextInput::make('sku')
////                    ->required()
////                    ->maxLength(255),
//
//                Textarea::make('description')
//                    ->columnSpanFull(),
//
//                /* -------------------------
//                 | Product Behaviour
//                 |--------------------------*/
//                Select::make('type')
//                    ->required()
//                    ->options([
//                        'stock'          => 'Stock Product',
//                        'service'        => 'Service',
//                        'measured_stock' => 'Measured Stock (Fuel)',
//                        'custom'         => 'Custom Item',
//                    ])
//                    ->reactive(),
//
//                Select::make('unit')
//                    ->required()
//                    ->options([
//                        'pcs'   => 'Pieces',
//                        'liter' => 'Liter',
//                        'gram'  => 'Gram',
//                        'kg'    => 'Kilogram',
//                        'job'   => 'Job',
//                        'hour'  => 'Hour',
//                        'day'   => 'Day',
//                        'sqm'   => 'Square Meter',
//                        'set'   => 'Set',
//                    ]),
//
//
//
//                /* -------------------------
//                 | Pricing
//                 |--------------------------*/
////                TextInput::make('purchase_price')
////                    ->numeric()
////                    ->prefix('$')
////                    ->visible(fn ($get) => $get('type') === 'stock'),
////
////                TextInput::make('selling_price')
////                    ->numeric()
////                    ->prefix('$')
////                    ->visible(fn ($get) => ! $get('is_variable_price')),
//
//                /* -------------------------
//                 | Relationships
//                 |--------------------------*/
//                Hidden::make('merchant_id')
//                    ->default(fn () => Filament::auth()->user()->id)
//                    ->dehydrated()
//                    ->required(),
//
//
//                Select::make('business_id')
//                    ->relationship('business', 'name')
//                    ->searchable()
//                    ->preload()
//                    ->required(),
//
//                Select::make('brand_id')
//                    ->relationship('brand', 'name')
//                    ->searchable()
//                    ->preload()
//                    ->nullable(),
//
//                Select::make('brand_model_id')
//                    ->relationship('brandModel', 'name')
//                    ->searchable()
//                    ->preload()
//                    ->nullable(),
//
//                Toggle::make('track_inventory')
//                    ->label('Track Inventory')
//                    ->default(true),
//
//                Toggle::make('is_variable_price')
//                    ->label('Variable / Runtime Pricing')
//                    ->default(false),
//                /* -------------------------
//                 | Status
//                 |--------------------------*/
//                Toggle::make('is_active')
//                    ->required(),
//            ]);
//    }
//}


namespace App\Filament\Resources\Products\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            \Filament\Schemas\Components\Section::make('Classification')
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('sub_category_id')
                        ->relationship('subCategory', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('brand_id')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('brand_model_id')
                        ->relationship('brandModel', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),

            /* =========================
             | PRODUCT
             |=========================*/
            \Filament\Schemas\Components\Section::make('Product')
                ->columnSpanFull()
                ->schema([

                    Hidden::make('merchant_id')
                        ->default(fn () => Filament::auth()->user()?->id)
                        ->dehydrated()
                        ->required(),

                    TextInput::make('name')
                        ->label('Product Name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('sku')
                        ->label('SKU')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-generated on save.'),

                    Textarea::make('description')
                        ->columnSpanFull(),

                    \Filament\Schemas\Components\Section::make('Basics')
                        ->columns(3)
                        ->schema([
                            Select::make('business_id')
                                ->relationship('business', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('type')
                                ->required()
                                ->options([
                                    'stock'          => 'Stock',
                                    'service'        => 'Service',
                                    'measured_stock' => 'Measured',
                                    'custom'         => 'Custom',
                                ])
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $set) =>
                                $state === 'service' ? $set('track_inventory', false) : null
                                ),

                            Select::make('unit')
                                ->required()
                                ->options([
                                    'pcs' => 'Pieces',
                                    'liter'  => 'Liter',
                                    'gram'   => 'Gram',
                                    'kg'     => 'Kilogram',
                                    'job'    => 'Job',
                                    'hour'   => 'Hour',
                                    'day'    => 'Day',
                                    'sqm'    => 'Square Meter',
                                    'set'    => 'Set',
                                ]),
                        ]),

                    \Filament\Schemas\Components\Section::make('Pricing & Inventory')
                        ->columns(3)
                        ->schema([
                            TextInput::make('purchase_price')
                                ->numeric()
                                ->visible(fn ($get) => $get('type') === 'stock'),

                            TextInput::make('selling_price')
                                ->numeric()
                                ->visible(fn ($get) => ! $get('is_variable_price')),

                            Toggle::make('track_inventory')->default(true),
                            Toggle::make('is_variable_price')->default(false),
                            Toggle::make('is_active')->default(true),
                        ]),
                ]),

            /* =========================
             | OPTIONS
             |=========================*/
            \Filament\Schemas\Components\Section::make('Custom Fields')
                ->columnSpanFull()
                ->description('Define attributes like Size, Color, Voltage, Karat.')
                ->schema([
                    Repeater::make('options')
                        ->columnSpanFull()
                        ->collapsible()
                        ->itemLabel(fn ($state) => $state['display_name'] ?? $state['name'] ?? 'Option')
                        ->schema([
                            TextInput::make('name')
                                ->label('Option Key')
                                ->required()
                                ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('name', Str::lower($state))
                                ),

                            TextInput::make('display_name')
                                ->label('Display Name')
                                ->nullable(),

                            Repeater::make('values')
                                ->label('Values')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('value')->required(),
                                ])
                                ->minItems(1)
                                ->columns(3),
                        ]),
                ]),
        ]);
    }

}
