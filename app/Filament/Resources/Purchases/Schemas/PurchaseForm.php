<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Purchase Information')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('purchase_no')
                        ->label('Purchase Number')
                        ->default(fn() => 'PUR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    DatePicker::make('purchase_date')
                        ->label('Purchase Date')
                        ->default(now())
                        ->required()
                        ->displayFormat('d/m/Y'),



                    Hidden::make('merchant_id')
                        ->default(function () {
                            $user = Filament::auth()->user();

                            return match (true) {
                                $user instanceof \App\Models\Merchant => $user->id,
                                $user instanceof \App\Models\User     => $user->merchant_id,
                                default                               => null,
                            };
                        })
                        ->required(),

                    Select::make('business_id')
                        ->label('Business')
                        ->relationship(
                            'business',
                            'name',
                            function (Builder $query) {
                                $user = Filament::auth()->user();

                                $merchantId = match (true) {
                                    $user instanceof \App\Models\Merchant => $user->id,
                                    $user instanceof \App\Models\User     => $user->merchant_id,
                                    default                               => null,
                                };

                                if (! $merchantId) {
                                    $query->whereRaw('1 = 0');
                                    return;
                                }

                                $query->where('merchant_id', $merchantId);

                                // 🔵 STAFF → ONLY assigned businesses (pivot)
                                if ($user instanceof \App\Models\User) {
                                    $query->whereHas('users', fn ($q) =>
                                    $q->where('users.id', $user->id)
                                    );
                                }
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->live()
                        ->afterStateUpdated(function (callable $set,$livewire){
                             $set('branch_id', null);
                            $livewire->resetValidation('data.business_id');
                            $livewire->resetErrorBag('data.business_id');
                    }),


                    Select::make('branch_id')
                        ->label('Branch')
                        ->relationship(
                            'branch',
                            'name',
                            function (Builder $query, callable $get) {
                                $user = Filament::auth()->user();
                                $businessId = $get('business_id');

                                if (! $businessId) {
                                    $query->whereRaw('1 = 0');
                                    return;
                                }

                                $merchantId = match (true) {
                                    $user instanceof \App\Models\Merchant => $user->id,
                                    $user instanceof \App\Models\User     => $user->merchant_id,
                                    default                               => null,
                                };

                                if (! $merchantId) {
                                    $query->whereRaw('1 = 0');
                                    return;
                                }

                                $query
                                    ->where('merchant_id', $merchantId)
                                    ->where('business_id', $businessId);

                                // 🔵 STAFF → ONLY assigned branches (pivot)
                                if ($user instanceof \App\Models\User) {
                                    $query->whereHas('users', fn ($q) =>
                                    $q->where('users.id', $user->id)
                                    );
                                }
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                         ->reactive()
                        ->live()
                        ->afterStateUpdated(function ($livewire){

                            $livewire->resetValidation('data.branch_id');
                            $livewire->resetErrorBag('data.branch_id');
                        }),


        ]),

            Section::make('Purchase Items')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('items')
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->searchable()
                                ->live()
                                ->preload()
                                ->options(function (callable $get): array {

                                    $businessId = $get('../../business_id');
                                    $branchId   = $get('../../branch_id');

                                    if (! $businessId || ! $branchId) {
                                        return [];
                                    }

                                    $user = Filament::auth()->user();

                                    $merchantId = match (true) {
                                        $user instanceof \App\Models\Merchant => $user->id,
                                        $user instanceof \App\Models\User     => $user->merchant_id,
                                        default                               => null,
                                    };

                                    $query = Product::query()
                                        ->select('products.id', 'products.name', 'products.sku')
                                        ->where('products.is_active', true)
                                        ->where('products.merchant_id', $merchantId)

                                        ->whereExists(function ($q) use ($businessId) {
                                            $q->selectRaw(1)
                                                ->from('business_products')
                                                ->whereColumn('business_products.product_id', 'products.id')
                                                ->where('business_products.business_id', $businessId);
                                        })

                                        ->whereExists(function ($q) use ($branchId) {
                                            $q->selectRaw(1)
                                                ->from('branch_products')
                                                ->whereColumn('branch_products.product_id', 'products.id')
                                                ->where('branch_products.branch_id', $branchId);
                                        });


                                    return $query
                                        ->orderBy('products.name')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn (Product $p) => [
                                            $p->id => "{$p->name} ({$p->sku})",
                                        ])
                                        ->all();
                                })

                                ->getSearchResultsUsing(function (string $search, callable $get): array {

                                    if (mb_strlen($search) < 1) {
                                        return [];
                                    }

                                    $businessId = $get('../../business_id');
                                    $branchId   = $get('../../branch_id');

                                    if (! $businessId || ! $branchId) {
                                        return [];
                                    }

                                    $user = Filament::auth()->user();

                                    $query = Product::query()
                                        ->select('products.id', 'products.name', 'products.sku')
                                        ->where('products.is_active', true)

                                        ->whereExists(function ($q) use ($businessId) {
                                            $q->selectRaw(1)
                                                ->from('business_products')
                                                ->whereColumn('business_products.product_id', 'products.id')
                                                ->where('business_products.business_id', $businessId);
                                        })

                                        ->whereExists(function ($q) use ($branchId) {
                                            $q->selectRaw(1)
                                                ->from('branch_products')
                                                ->whereColumn('branch_products.product_id', 'products.id')
                                                ->where('branch_products.branch_id', $branchId);
                                        })

                                        ->where(function ($q) use ($search) {
                                            $q->where('products.name', 'ilike', "%{$search}%")
                                                ->orWhere('products.sku', 'ilike', "%{$search}%");
                                        });


                                    return $query
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn (Product $p) => [
                                            $p->id => "{$p->name} ({$p->sku})",
                                        ])
                                        ->all();
                                })

                                ->getOptionLabelUsing(function ($value): ?string {
                                    if (! $value) {
                                        return null;
                                    }

                                    $product = Product::query()
                                        ->select(['id', 'name', 'sku'])
                                        ->find($value);

                                    return $product
                                        ? "{$product->name} ({$product->sku})"
                                        : null;
                                })

                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get,$livewire) {

                                    $livewire->resetValidation('data.items.*.product_id');
                                    $livewire->resetErrorBag('data.items.*.product_id');
                                    if (! $state) {
                                        return;
                                    }

                                    $product = Product::query()
                                        ->select(['id', 'purchase_price'])
                                        ->find($state);

                                    if (! $product) {
                                        return;
                                    }

                                    $qty  = (float) ($get('quantity') ?? 1);
                                    $unit = (float) ($product->purchase_price ?? 0);

                                    $set('unit_price', $unit);
                                    $set('line_total', $unit * $qty);

                                    PurchaseForm::recalcTotals($set, $get);
                                }),



                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->reactive()
                                ->debounce(300)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $unit = (float)($get('unit_price') ?? 0);
                                    $qty = (float)($state ?? 0);

                                    $set('line_total', $unit * $qty);

                                    self::recalcTotals($set, $get);
                                }),

                            TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->reactive()
                                ->debounce(300)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = (float)($get('quantity') ?? 1);
                                    $unit = (float)($state ?? 0);

                                    $set('line_total', $unit * $qty);

                                    self::recalcTotals($set, $get);
                                }),

                            TextInput::make('line_total')
                                ->label('Line Total')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->default(0),
                        ])
                        ->columns(4)
                        ->defaultItems(1)
                        ->minItems(1)
                        ->collapsible()
                        // ✅ IMPORTANT: no DB calls here
                        ->itemLabel(fn(array $state): string => $state['product_id'] ? 'Item' : 'New Item')
                        ->addActionLabel('Add Item')
                        ->reorderable(false)
                        ->deletable(true)
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            // Called when items added/removed
                            self::recalcTotals($set, $get);
                        }),
                ]),

            Section::make('Summary')
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Placeholder::make('subtotal_display')
                        ->label('Subtotal')
                        ->content(fn(callable $get): string => number_format((float)($get('subtotal') ?? 0), 2)),

                    TextInput::make('discount')
                        ->label('Discount')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->reactive()
                        ->debounce(300)
                        ->afterStateUpdated(fn($state, callable $set, callable $get) => self::recalcTotals($set, $get)),

                    TextInput::make('tax')
                        ->label('Tax')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->reactive()
                        ->debounce(300)
                        ->afterStateUpdated(fn($state, callable $set, callable $get) => self::recalcTotals($set, $get)),

                    Placeholder::make('total_amount_display')
                        ->label('Total Amount')
                        ->content(fn(callable $get): string => number_format((float)($get('total_amount') ?? 0), 2)),

                    Hidden::make('subtotal')->default(0)->dehydrated(),
                    Hidden::make('total_amount')->default(0)->dehydrated(),
                ]),

            Section::make('Notes')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    private static function recalcTotals(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];
        $subtotal = collect($items)->sum(fn($item) => (float)($item['line_total'] ?? 0));

        $discount = (float)($get('discount') ?? 0);
        $tax = (float)($get('tax') ?? 0);

        $set('subtotal', $subtotal);
        $set('total_amount', $subtotal - $discount + $tax);
    }
}
