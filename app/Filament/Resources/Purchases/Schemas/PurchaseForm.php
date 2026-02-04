<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\View;
use Illuminate\Support\Facades\DB;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Grid::make(2)
                ->columnSpanFull()
                ->schema([

                    /* ===========================
                     * PURCHASE INFORMATION
                     * =========================== */
                    Section::make('Purchase Information')
                        ->schema([
                            TextInput::make('purchase_no')
                                ->label('Purchase Number')
                                ->default(fn () => 'PUR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)))
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.purchase_no');
                                    $livewire->resetErrorBag('data.purchase_no');
                                }),

                            DatePicker::make('purchase_date')
                                ->label('Purchase Date')
                                ->default(now())
                                ->required()
                                ->displayFormat('d/m/Y')
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.purchase_date');
                                    $livewire->resetErrorBag('data.purchase_date');
                                }),

                            Hidden::make('merchant_id')
                                ->default(fn () => self::merchantId())
                                ->required(),
                        ]),

                    /* ===========================
                     * MERCHANT CARD / LOGO
                     * =========================== */
                    Grid::make(1)
                        ->extraAttributes([
                            'class' => 'h-full flex items-center justify-center',
                        ])
                        ->schema([
                            FileUpload::make('merchant_logo')
                                ->label('')
                                ->extraAttributes(['class' => 'merchant-logo-center'])
                                ->image()
                                ->disk('public')
                                ->directory('merchants/logos')
                                ->imagePreviewHeight(140)
                                ->visible(fn () => ! self::merchantHasLogo())
                                ->dehydrated(false),

                            View::make('filament.pages.merchant-card')
                                ->extraAttributes(['class' => 'merchant-logo-center'])
                                ->visible(fn () => self::merchantHasLogo()),
                        ]),
                ]),

            /* ===========================
             * PURCHASE ITEMS
             * =========================== */
            Section::make('Purchase Items')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('items')
                        ->schema([

//                            /* -------- BUSINESS -------- */
//                            Select::make('business_id')
//                                ->label('Business')
//                                ->searchable()
//                                ->preload()
//                                ->required()
//                                ->options(fn () =>
//                                \App\Models\Business::query()
//                                    ->where('merchant_id', self::merchantId())
//                                    ->orderBy('name')
//                                    ->pluck('name', 'id')
//                                    ->toArray()
//                                )
//                                ->reactive()
//                                ->afterStateUpdated(fn (callable $set) => [
//                                    $set('branch_id', null),
//                                    $set('product_id', null),
//                                    $set('product_variant_id', null),
//                                ]),

                            /* -------- BRANCH -------- */



        /* -------- PRODUCT -------- */
                            Select::make('product_id')
                                ->label('Product')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->reactive()
                                ->options(function () {

                                    $user = Filament::auth()->user();

                                    // MERCHANT → all products
                                    if ($user instanceof \App\Models\Merchant) {
                                        return Product::query()
                                            ->where('is_active', true)
                                            ->where('merchant_id', self::merchantId())
                                            ->orderBy('name')
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} ({$p->sku})"])
                                            ->all();
                                    }

                                    // STAFF → only products linked to their branches
                                    $branchIds = $user->branches()->pluck('branches.id');

                                    return Product::query()
                                        ->where('products.is_active', true)
                                        ->where('products.merchant_id', self::merchantId())
                                        ->whereExists(function ($q) use ($branchIds) {
                                            $q->selectRaw(1)
                                                ->from('branch_products')
                                                ->whereColumn('branch_products.product_id', 'products.id')
                                                ->whereIn('branch_products.branch_id', $branchIds);
                                        })
                                        ->orderBy('products.name')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} ({$p->sku})"])
                                        ->all();
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.product_id');
                                    $livewire->resetErrorBag('data.items.*.product_id');

                                    // Reset dependents when product changes
                                    $set('branch_id', null);
                                    $set('product_variant_id', null);

                                    if (! $state) {
                                        return;
                                    }

                                    $user = Filament::auth()->user();

                                    $branchQuery = \App\Models\Branch::query()
                                        ->where('merchant_id', self::merchantId())
                                        ->whereExists(function ($q) use ($state) {
                                            $q->selectRaw(1)
                                                ->from('branch_products')
                                                ->whereColumn('branch_products.branch_id', 'branches.id')
                                                ->where('branch_products.product_id', $state);
                                        });

                                    if ($user instanceof \App\Models\User) {
                                        $branchQuery->whereIn(
                                            'branches.id',
                                            $user->branches()->pluck('branches.id')
                                        );
                                    }

                                    $branchIds = $branchQuery->pluck('branches.id');

                                    // ✅ Auto-select if only one branch exists
                                    if ($branchIds->count() === 1) {
                                        $set('branch_id', $branchIds->first());
                                        $livewire->resetValidation('data.items.*.branch_id');
                                        $livewire->resetErrorBag('data.items.*.branch_id');
                                    }

                                    // Pricing
                                    $product = Product::select(['id', 'purchase_price'])->find($state);
                                    if (! $product) {
                                        return;
                                    }

                                    $qty  = (float) ($get('quantity') ?? 1);
                                    $unit = (float) ($product->purchase_price ?? 0);

                                    $set('unit_price', $unit);
                                    $set('line_total', $unit * $qty);

                                    self::recalcTotals($set, $get);
                                }),




        /* -------- VARIANT -------- */
                            Select::make('product_variant_id')
                                ->label('Product Variant')
                                ->searchable()
                                ->live()
                                ->reactive()
                                ->required()
                                ->options(function (callable $get): array {
                                    $productId = $get('product_id');

                                    if (! $productId) {
                                        return [];
                                    }

                                    return \App\Models\ProductVariant::query()
                                        ->where('product_id', $productId)
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function ($variant) {
                                            $label =
                                                $variant->name
                                                ?? $variant->sku
                                                ?? substr($variant->id, 0, 8);

                                            return [$variant->id => $label];
                                        })
                                        ->all();
                                })

                                /**
                                 * 🔑 CRITICAL: Re-apply value AFTER options exist
                                 */
                                ->afterStateHydrated(function (callable $set, callable $get) {
                                    $productId = $get('product_id');
                                    $variantId = $get('product_variant_id');

                                    if (! $productId || ! $variantId) {
                                        return;
                                    }

                                    // Force Filament to re-bind value
                                    $set('product_variant_id', $variantId);
                                })

                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.product_variant_id');
                                    $livewire->resetErrorBag('data.items.*.product_variant_id');
                                    if (! $state) {
                                        return;
                                    }

                                    $variant = \App\Models\ProductVariant::select(['id', 'purchase_price'])->find($state);

                                    if (! $variant) {
                                        return;
                                    }

                                    $qty  = (float) ($get('quantity') ?? 1);
                                    $unit = (float) ($variant->purchase_price ?? 0);

                                    $set('unit_price', $unit);
                                    $set('line_total', $unit * $qty);

                                    self::recalcTotals($set, $get);
                                }),


                                Select::make('branch_id')
                                ->label('Branch')
                                ->searchable()
                                ->required()
                                ->live()
                                ->reactive()
                                ->allowHtml() // ✅ required for indentation
                                ->options(function (callable $get): array {

                                    $productId = $get('product_id');
                                    if (! $productId) {
                                        return [];
                                    }

                                    $user = Filament::auth()->user();

                                    $query = \App\Models\Branch::query()
                                        ->with('business')
                                        ->where('merchant_id', self::merchantId())
                                        ->whereExists(function ($q) use ($productId) {
                                            $q->selectRaw(1)
                                                ->from('branch_products')
                                                ->whereColumn('branch_products.branch_id', 'branches.id')
                                                ->where('branch_products.product_id', $productId);
                                        });

                                    // Staff → only assigned branches
                                    if ($user instanceof \App\Models\User) {
                                        $query->whereIn(
                                            'branches.id',
                                            $user->branches()->pluck('branches.id')
                                        );
                                    }

                                    return $query
                                        ->orderBy('business_id')
                                        ->orderBy('branches.name')
                                        ->get()
                                        ->groupBy(fn ($branch) => $branch->business?->name ?? 'Other')
                                        ->map(fn ($group) =>
                                        $group->pluck('name', 'id')
                                            ->map(fn ($name) => '&nbsp;&nbsp;&nbsp;&nbsp;' . e($name))
                                            ->toArray()
                                        )
                                        ->toArray();
                                })
                                ->afterStateHydrated(function (callable $set, callable $get) {

                                    // Auto-select branch if ONLY one exists
                                    $productId = $get('product_id');
                                    if (! $productId || $get('branch_id')) {
                                        return;
                                    }

                                    $branches = \App\Models\Branch::query()
                                        ->where('merchant_id', self::merchantId())
                                        ->whereExists(function ($q) use ($productId) {
                                            $q->selectRaw(1)
                                                ->from('branch_products')
                                                ->whereColumn('branch_products.branch_id', 'branches.id')
                                                ->where('branch_products.product_id', $productId);
                                        })
                                        ->pluck('branches.id');

                                    if ($branches->count() === 1) {
                                        $set('branch_id', $branches->first());
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.branch_id');
                                    $livewire->resetErrorBag('data.items.*.branch_id');
                                }),

                            /* -------- QUANTITY -------- */
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->live(debounce: 300)
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.quantity');
                                    $livewire->resetErrorBag('data.items.*.quantity');
                                    if ($state === null || $state === '') {
                                        $set('line_total', 0);
                                        self::recalcTotals($set, $get);
                                        return;
                                    }
                                    $qty = max(1, (float) ($state ?? 1));
                                    $unit = (float) ($get('unit_price') ?? 0);

                                    $set('quantity', $qty);
                                    $set('line_total', $unit * $qty);

                                    self::recalcTotals($set, $get);
                                }),

                            /* -------- UNIT PRICE -------- */
                            TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->live(debounce: 300)
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.unit_price');
                                    $livewire->resetErrorBag('data.items.*.unit_price');
                                    $unit = max(0, (float) ($state ?? 0));
                                    $qty = (float) ($get('quantity') ?? 1);

                                    $set('unit_price', $unit);
                                    $set('line_total', $unit * $qty);

                                    self::recalcTotals($set, $get);
                                }),

                            /* -------- LINE TOTAL -------- */
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
                        ->itemLabel(fn (array $state): string => $state['product_id'] ? 'Item' : 'New Item')
                        ->addActionLabel('Add Item')
                        ->reorderable(false)
                        ->deletable(true)
                        ->afterStateUpdated(fn (callable $set, callable $get) =>
                        self::recalcTotals($set, $get)
                        ),
                ]),

            /* ===========================
             * SUMMARY
             * =========================== */
            Section::make('Summary')
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Placeholder::make('subtotal_display')
                        ->label('Subtotal')
                        ->content(fn (callable $get) =>
                        number_format((float) ($get('subtotal') ?? 0), 2)
                        ),

                    TextInput::make('discount')
                        ->label('Discount (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->suffix('%')
                        ->reactive()
                        ->afterStateHydrated(function ($state, callable $set) {
                            if ($state === null || $state === '') {
                                $set('discount', 0);
                            }
                        })
                        ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : $state)
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                        self::recalcTotals($set, $get)
                        ),

                    TextInput::make('tax')
                        ->label('Tax (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->suffix('%')
                        ->reactive()
                        ->afterStateHydrated(function ($state, callable $set) {
                            if ($state === null || $state === '') {
                                $set('tax', 0);
                            }
                        })
                        ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : $state)
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                        self::recalcTotals($set, $get)
                        ),

                    Placeholder::make('total_amount_display')
                        ->label('Total Amount')
                        ->content(fn (callable $get) =>
                        number_format((float) ($get('total_amount') ?? 0), 2)
                        ),

                    Hidden::make('subtotal')->default(0)->dehydrated(),
                    Hidden::make('total_amount')->default(0)->dehydrated(),
                ]),

            /* ===========================
             * NOTES
             * =========================== */
            Section::make('Notes')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('notes')
                        ->maxLength(255)
                        ->rows(3),
                ]),
        ]);
    }

    /* ======================================================
     * HELPERS (DO NOT REMOVE)
     * ====================================================== */

    private static function merchantId(): ?string
    {
        $user = Filament::auth()->user();

        return match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };
    }

    private static function merchantHasLogo(): bool
    {
        $user = Filament::auth()->user();

        $merchant = $user instanceof \App\Models\Merchant
            ? $user
            : $user?->merchant;

        return (bool) $merchant?->logo;
    }

    private static function recalcTotals(callable $set, callable $get): void
    {
        $items = $get('items');
        $rootPrefix = '';

        if (! is_array($items)) {
            $items = $get('../../items') ?? [];
            $rootPrefix = '../../';
        }

        $subtotal = collect($items)->sum(fn ($item) => (float) ($item['line_total'] ?? 0));

        $discountRate = $get('discount');
        $taxRate      = $get('tax');

        if ($discountRate === null) {
            $discountRate = $get('../../discount');
        }

        if ($taxRate === null) {
            $taxRate = $get('../../tax');
        }

        $discountRate = (float) ($discountRate ?? 0);
        $taxRate      = (float) ($taxRate ?? 0);

        $discountRate = max(0, min(100, $discountRate));
        $taxRate = max(0, min(100, $taxRate));

        $discountAmount = $subtotal * ($discountRate / 100);
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = $taxableAmount * ($taxRate / 100);

        $set($rootPrefix . 'subtotal', $subtotal);
        $set($rootPrefix . 'total_amount', $taxableAmount + $taxAmount);
    }
}
