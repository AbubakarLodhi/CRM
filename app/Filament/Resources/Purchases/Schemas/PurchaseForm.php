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
                                ->unique(ignoreRecord: true),

                            DatePicker::make('purchase_date')
                                ->label('Purchase Date')
                                ->default(now())
                                ->required()
                                ->displayFormat('d/m/Y'),

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
                                ->image()
                                ->disk('public')
                                ->directory('merchants/logos')
                                ->imagePreviewHeight(140)
                                ->visible(fn () => ! self::merchantHasLogo())
                                ->dehydrated(false),

                            View::make('filament.pages.merchant-card')
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
                            Select::make('branch_id')
                                ->label('Branch')
                                ->searchable()
                                ->required()
                                ->options(function () {
                                    $user = Filament::auth()->user();

                                    // Merchant → all branches
                                    if ($user instanceof \App\Models\Merchant) {
                                        return \App\Models\Branch::query()
                                            ->where('merchant_id', self::merchantId())
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    }

                                    // Staff → only assigned branches
                                    return $user->branches()
                                        ->orderBy('branches.name')
                                        ->pluck('branches.name', 'branches.id')
                                        ->toArray();
                                })
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, callable $get) {

                                    // Reset dependent fields (UNCHANGED BEHAVIOR)
                                    $set('product_id', null);
                                    $set('product_variant_id', null);

                                    $user = Filament::auth()->user();

                                    // Only auto-resolve for STAFF
                                    if (! $user instanceof \App\Models\User) {
                                        return;
                                    }

                                    // Do NOT override if branch already selected
                                    if ($get('branch_id')) {
                                        return;
                                    }

                                    // If staff has only ONE branch → auto-select
                                    $branchIds = $user->branches()->pluck('branches.id');

                                    if ($branchIds->count() === 1) {
                                        $set('branch_id', $branchIds->first());
                                    }
                                })

                                ->afterStateHydrated(function (callable $set, callable $get) {
                                    $user = Filament::auth()->user();

                                    if (! $user instanceof \App\Models\User) {
                                        return;
                                    }

                                    // If already set, do nothing
                                    if ($get('branch_id')) {
                                        return;
                                    }

                                    $branchIds = $user->branches()->pluck('branches.id');

                                    if ($branchIds->count() === 1) {
                                        $set('branch_id', $branchIds->first());
                                    }
                                })
                            ,


                            /* -------- PRODUCT -------- */
                            Select::make('product_id')
                                ->label('Product')
                                ->searchable()
                                ->live()
                                ->preload()
                                ->options(function (callable $get): array {



                                    $branchId = $get('branch_id');

                                    if (! $branchId) {
                                        return [];
                                    }

                                    $businessId = \App\Models\Branch::where('id', $branchId)
                                        ->value('business_id');

                                    if (! $businessId || ! $branchId) {
                                        return [];
                                    }

                                    return Product::query()
                                        ->where('products.is_active', true)
                                        ->where('products.merchant_id', self::merchantId())
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

                                    $branchId = $get('branch_id');

                                    if (! $branchId) {
                                        return [];
                                    }

                                    $businessId = \App\Models\Branch::where('id', $branchId)
                                        ->value('business_id');


                                    if (! $businessId || ! $branchId) {
                                        return [];
                                    }

                                    return Product::query()
                                        ->where('products.is_active', true)
                                        ->where('products.merchant_id', self::merchantId())
                                        ->where(function ($q) use ($search) {
                                            $q->where('products.name', 'ilike', "%{$search}%")
                                                ->orWhere('products.sku', 'ilike', "%{$search}%");
                                        })
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
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn (Product $p) => [
                                            $p->id => "{$p->name} ({$p->sku})",
                                        ])
                                        ->all();
                                })
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {

                                    $livewire->resetValidation('data.items.*.product_id');
                                    $livewire->resetErrorBag('data.items.*.product_id');

                                    if (! $state) {
                                        return;
                                    }

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
                                                ?? $variant->option_values
                                                ?? substr($variant->id, 0, 8);

                                            return [$variant->id => $label];
                                        })
                                        ->all();
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {

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

                            /* -------- QUANTITY -------- */
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->reactive()
                                ->debounce(300)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
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
                                ->reactive()
                                ->debounce(300)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
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
                        ->numeric()
                        ->default(0)
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                        self::recalcTotals($set, $get)
                        ),

                    TextInput::make('tax')
                        ->numeric()
                        ->default(0)
                        ->reactive()
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
        $items = $get('items') ?? [];
        $subtotal = collect($items)->sum(fn ($item) => (float) ($item['line_total'] ?? 0));

        $discount = (float) ($get('discount') ?? 0);
        $tax      = (float) ($get('tax') ?? 0);

        $set('subtotal', $subtotal);
        $set('total_amount', $subtotal - $discount + $tax);
    }
}
