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

use App\Models\Admin;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
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
                        ->relationship(
                            name: 'category',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query) {
                                $user = Filament::auth()->user();

                                if (! $user instanceof Admin) {
                                    $query->where('merchant_id', $user->id);
                                }

                                // parent categories only
                                $query->whereNull('parent_id');
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('sub_category_id')
                        ->relationship(
                            name: 'subCategory',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query, callable $get) {
                                $user = Filament::auth()->user();

                                if (! $user instanceof Admin) {
                                    $query->where('merchant_id', $user->id);
                                }

                                if ($categoryId = $get('category_id')) {
                                    $query->where('parent_id', $categoryId);
                                } else {
                                    // do not show all sub-categories
                                    $query->whereRaw('1 = 0');
                                }
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('brand_id')
                        ->relationship(
                            name: 'brand',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query, callable $get) {
                                $user = Filament::auth()->user();

                                if (! $user instanceof Admin) {
                                    $query->where('merchant_id', $user->id);
                                }

                                if ($subCategoryId = $get('sub_category_id')) {
                                    // 🔥 only brands linked to this sub-category
                                    $query->where('category_id', $subCategoryId);
                                } else {
                                    $query->whereRaw('1 = 0');
                                }
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('brand_model_id')
                        ->relationship(
                            name: 'brandModel',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query, callable $get) {
                                $user = Filament::auth()->user();

                                if (! $user instanceof Admin) {
                                    $query->where('merchant_id', $user->id);
                                }

                                if ($brandId = $get('brand_id')) {
                                    $query->where('brand_id', $brandId);
                                } else {
                                    $query->whereRaw('1 = 0');
                                    return;
                                }

                                if ($subCategoryId = $get('sub_category_id')) {
                                    $query->whereHas('brand', function (Builder $q) use ($subCategoryId) {
                                        $q->where('category_id', $subCategoryId);
                                    });
                                } else {
                                    $query->whereRaw('1 = 0');
                                }
                            }
                        )
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

                    Select::make('merchant_id')
                        ->label('Merchant')
                        ->relationship('merchant', 'name')
                        ->visible(fn() => Filament::auth()->user() instanceof Admin),

                    Hidden::make('merchant_id')
                        ->default(fn() => Filament::auth()->user()?->id)
                        ->visible(fn() => !(Filament::auth()->user() instanceof Admin)),

                    TextInput::make('name')
                        ->label('Product Name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    FileUpload::make('product_image')
                        ->label('Product Image')
                        ->image()
                        ->disk('public')
                        ->directory('products/images')
                        ->imagePreviewHeight(150)
                        ->maxSize(2048)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->saveUploadedFileUsing(fn ($file) =>
                        $file->store('products/images', 'public')
                        ),

                    TextInput::make('sku')
                        ->label('SKU')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-generated on save.'),

                    Textarea::make('description')
                        ->columnSpanFull(),

                    \Filament\Schemas\Components\Section::make('Basics')
                        ->columns(2)
                        ->schema([
//                            Select::make('business_id')
//                                ->label('Business')
//                                ->relationship(
//                                    name: 'business',
//                                    titleAttribute: 'name',
//                                    modifyQueryUsing: function (Builder $query) {
//                                        $user = Filament::auth()->user();
//
//                                        if ($user instanceof Admin) {
//                                            return;
//                                        }
//
//                                        // Merchant → only their businesses
//                                        $query->where('merchant_id', $user->id);
//                                    }
//                                )
//                                ->preload()
//                                ->searchable()
//                                ->required(),

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

            Section::make('Custom Fields')
                ->columnSpanFull()
                ->description('Define dynamic attributes like Size, Color, Voltage, Karat.')
                ->schema([
                    Repeater::make('options')
                        ->columnSpanFull()
                        ->collapsible()
                        ->columns(2)
                        ->itemLabel(fn ($state) => $state['display_name'] ?? $state['name'] ?? 'Custom Field')
                        ->schema([

                            Select::make('field_type')
                                ->label('Type')
                                ->required()
                                ->options([
                                    'select'     => 'Select',
                                    'numeric'    => 'Numeric',
                                    'alphabetic' => 'Text',
                                ])
                                ->reactive(),

//                            Toggle::make('is_required')
//                                ->label('Required')
//                                ->default(false),

                            TextInput::make('name')
                                ->label('Label')
                                ->required(),
//
//                            TextInput::make('display_name')
//                                ->label('Label')
//                                ->required(),

                            // ✅ Only for "select" type
                            Repeater::make('values')
                                ->label('Options')
                                ->columnSpanFull()
                                ->visible(fn ($get) => $get('field_type') === 'select')
                                ->columns(2)
                                ->grid(2)
                                ->schema([
                                    TextInput::make('value')
                                        ->required()
                                        ->placeholder('e.g. Small')
                                        ->hiddenLabel(),
                                ])
                                ->minItems(1)
                                ->addActionLabel('+'),

                            TextInput::make('min')
                                ->label('Min')
                                ->numeric()
                                ->visible(fn ($get) => $get('field_type') === 'numeric'),

                            TextInput::make('max')
                                ->label('Max')
                                ->numeric()
                                ->visible(fn ($get) => $get('field_type') === 'numeric'),
                        ])
                        ->addActionLabel('+ Add Field'),
                ]),

            Toggle::make('is_stocked')->default(true),
            Toggle::make('is_active')->default(true),

                    ])
            ]);

    }

}
