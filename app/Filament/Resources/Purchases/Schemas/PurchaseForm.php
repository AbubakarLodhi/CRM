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
use App\Models\Vendor;
use App\Filament\Resources\Vendors\Schemas\VendorForm;

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
                        ->extraAttributes(['class' => 'blue-section'])
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

                            Select::make('vendor_id')
                                ->label('Vendor')
                                ->relationship(
                                    'vendor',
                                    'name',
                                    fn ($query) => $query->where('merchant_id', self::merchantId())
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->suffixAction(
                                    \Filament\Actions\Action::make('createVendor')
                                        ->icon('heroicon-o-plus')
                                        ->tooltip('Create Vendor')
                                        ->modalHeading('Create Vendor')
                                        ->modalSubmitActionLabel('Create')
                                        ->modalWidth('lg')
                                        ->model(Vendor::class)
                                        ->form(VendorForm::components())
                                        ->action(fn (array $data, callable $set) =>
                                        $set('vendor_id', Vendor::create($data)->id)
                                        )
                                )
                                ->live()
                                ->afterStateUpdated(fn ($_, $__, $___, $livewire) => (
                                    $livewire->resetValidation('data.vendor_id') ||
                                    $livewire->resetErrorBag('data.vendor_id')
                                )),

                            Select::make('payment_type')
                                ->label('Payment Type')
                                ->options([
                                    'cash' => 'Cash',
                                    'credit' => 'Credit',
                                ])
                                ->default('cash')
                                ->required()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function ($state, $livewire) {
                                    $livewire->resetValidation('data.payment_type');
                                    $livewire->resetErrorBag('data.payment_type');
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
                ->extraAttributes(['class' => 'line-items-section'])
                ->columnSpanFull()
                ->headerActions([
                    \Filament\Actions\Action::make('usePercentMode')
                        ->label('Percent')
                        ->extraAttributes(fn (callable $get) => [
                            'class' => 'discount-mode-toggle left' . (($get('discount_mode') ?? 'percent') === 'percent' ? ' is-active' : ''),
                        ])
                        ->disabled(fn (callable $get) => ($get('discount_mode') ?? 'percent') === 'percent')
                        ->action(function (callable $set, callable $get) {
                            $items = $get('items') ?? [];

                            foreach ($items as &$item) {
                                $lineTotal = (float) ($item['line_subtotal'] ?? $item['line_total'] ?? 0);
                                $discountRate = (float) ($item['discount'] ?? 0);
                                $discountAmount = $lineTotal * ($discountRate / 100);
                                $taxableAmount = $lineTotal - $discountAmount;
                                $taxRate = (float) ($item['tax'] ?? 0);
                                $taxAmount = $taxableAmount * ($taxRate / 100);

                                $item['line_subtotal'] = $lineTotal;
                                $item['line_total'] = round($taxableAmount + $taxAmount, 2);
                                $item['discount_amount'] = round($discountAmount, 2);
                                $item['tax_amount'] = round($taxAmount, 2);
                            }

                            $set('items', $items);
                            $set('discount_mode', 'percent');
                        }),
                    \Filament\Actions\Action::make('useAmountMode')
                        ->label('Amount')
                        ->extraAttributes(fn (callable $get) => [
                            'class' => 'discount-mode-toggle right' . (($get('discount_mode') ?? 'percent') === 'amount' ? ' is-active' : ''),
                        ])
                        ->disabled(fn (callable $get) => ($get('discount_mode') ?? 'percent') === 'amount')
                        ->action(function (callable $set, callable $get) {
                            $items = $get('items') ?? [];

                            foreach ($items as &$item) {
                                $lineTotal = (float) ($item['line_subtotal'] ?? $item['line_total'] ?? 0);
                                $discountRate = (float) ($item['discount'] ?? 0);
                                $discountAmount = $lineTotal * ($discountRate / 100);
                                $taxableAmount = $lineTotal - $discountAmount;
                                $taxRate = (float) ($item['tax'] ?? 0);
                                $taxAmount = $taxableAmount * ($taxRate / 100);

                                $item['line_subtotal'] = $lineTotal;
                                $item['line_total'] = round($taxableAmount + $taxAmount, 2);
                                $item['discount_amount'] = round($discountAmount, 2);
                                $item['tax_amount'] = round($taxAmount, 2);
                            }

                            $set('items', $items);
                            $set('discount_mode', 'amount');
                        }),
                ])
                ->schema([
                    Hidden::make('discount_mode')
                        ->default('percent')
                        ->dehydrated(false),
                    Repeater::make('items')
                        ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                            $livewire->resetValidation();
                            $livewire->resetErrorBag();
                        })
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
                                    $set('unit_price', 0);
                                    $set('line_subtotal', 0);
                                    $set('line_total', 0);
                                    self::recalcTotals($set, $get);

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
                                        $set('unit_price', 0);
                                        $set('line_subtotal', 0);
                                        $set('line_total', 0);
                                        self::recalcTotals($set, $get);
                                        return;
                                    }

                                    $variant = \App\Models\ProductVariant::select(['id', 'purchase_price'])->find($state);

                                    if (! $variant) {
                                        return;
                                    }

                                    $qty  = (float) ($get('quantity') ?? 1);
                                    $unit = (float) ($variant->purchase_price ?? 0);

                                    $set('unit_price', $unit);
                                    $set('line_subtotal', $unit * $qty);
                                    self::updateLineTotalDisplay($set, $get);

                                    self::recalcTotals($set, $get);
                                }),


                            /* -------- QUANTITY -------- */
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->inputMode('numeric')
                                ->rule('numeric')
                                ->extraInputAttributes(['data-line-field' => 'quantity'])
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.quantity');
                                    $livewire->resetErrorBag('data.items.*.quantity');
                                    if ($state === null || $state === '' || ! is_numeric($state)) {
                                        return;
                                    }
                                    $qty = max(1, (float) ($state ?? 1));
                                    $unit = (float) ($get('unit_price') ?? 0);

                                    $set('line_subtotal', $unit * $qty);
                                    self::updateLineTotalDisplay($set, $get);

                                    self::recalcTotals($set, $get);
                                }),

                            /* -------- UNIT PRICE -------- */
                            TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->inputMode('decimal')
                                ->rule('numeric')
                                ->extraInputAttributes(['data-line-field' => 'unit_price'])
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.unit_price');
                                    $livewire->resetErrorBag('data.items.*.unit_price');
                                    if ($state === null || $state === '' || ! is_numeric($state)) {
                                        return;
                                    }
                                    $unit = max(0, (float) ($state ?? 0));
                                    $qty = (float) ($get('quantity') ?? 1);

                                    $set('line_subtotal', $unit * $qty);

                                    if (($get('../../discount_mode') ?? 'percent') === 'amount') {
                                        $lineTotal = (float) ($get('unit_price') ?? 0);
                                        $discountAmount = (float) ($get('discount_amount') ?? 0);
                                        $taxAmount = (float) ($get('tax_amount') ?? 0);

                                        $discountRate = $lineTotal > 0 ? ($discountAmount / $lineTotal) * 100 : 0;
                                        $taxableAmount = $lineTotal - ($lineTotal * ($discountRate / 100));
                                        $taxRate = $taxableAmount > 0 ? ($taxAmount / $taxableAmount) * 100 : 0;

                                        $set('discount', round($discountRate, 2));
                                        $set('tax', round($taxRate, 2));
                                    }

                                    self::updateLineTotalDisplay($set, $get);
                                    self::recalcTotals($set, $get);
                                }),

                            TextInput::make('discount')
                                ->label('Discount (%)')
                                ->inputMode('decimal')
                                ->rule('numeric')
                                ->extraInputAttributes(['data-line-field' => 'discount'])
                                ->default(0)
                                ->minValue(0)
                                ->rule('max:100')
                                ->validationMessages([
                                    'max' => 'The discount (%) field must not be greater than 100.',
                                ])
                                ->step(0.01)
                                ->suffix('%')
                                ->reactive()
                                ->afterStateHydrated(function ($state, callable $set) {
                                    if ($state === null || $state === '') {
                                        $set('discount', 0);
                                        return;
                                    }
                                    $set('discount', (float) $state);
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.discount');
                                    $livewire->resetErrorBag('data.items.*.discount');
                                    if ($state === null || $state === '' || ! is_numeric($state)) {
                                        return;
                                    }
                                    self::updateLineTotalDisplay($set, $get);
                                    self::recalcTotals($set, $get);
                                })
                                ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : $state)
                                ->visible(fn (callable $get) => ($get('../../discount_mode') ?? 'percent') !== 'amount'),

                            TextInput::make('discount_amount')
                                ->label('Discount (PKR)')
                                ->inputMode('decimal')
                                ->rule('numeric')
                                ->extraInputAttributes(['data-line-field' => 'discount_amount'])
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(function (callable $get) {
                                    $qty = (float) ($get('quantity') ?? 0);
                                    $unit = (float) ($get('unit_price') ?? 0);
                                    $lineSubtotal = $qty * $unit;

                                    return max(0, $lineSubtotal);
                                })
                                ->validationMessages([
                                    'max' => 'Discount amount cannot be greater than the line subtotal.',
                                ])
                                ->step(0.01)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.discount_amount');
                                    $livewire->resetErrorBag('data.items.*.discount_amount');
                                    if ($state === null || $state === '' || ! is_numeric($state)) {
                                        return;
                                    }
                                    $lineTotal = (float) ($get('line_subtotal') ?? 0);
                                    $amount = round(max(0, (float) ($state ?? 0)), 2);
                                    $rate = $lineTotal > 0 ? ($amount / $lineTotal) * 100 : 0;

                                    $set('discount', $rate);
                                    self::updateLineTotalDisplay($set, $get);
                                    self::recalcTotals($set, $get);
                                })
                                ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : $state)
                                ->visible(fn (callable $get) => ($get('../../discount_mode') ?? 'percent') === 'amount'),

                            TextInput::make('tax')
                                ->label('Tax (%)')
                                ->inputMode('decimal')
                                ->rule('numeric')
                                ->extraInputAttributes(['data-line-field' => 'tax'])
                                ->default(0)
                                ->minValue(0)
                                ->rule('max:100')
                                ->validationMessages([
                                    'max' => 'The tax (%) field must not be greater than 100.',
                                ])
                                ->default(16)
                                ->step(0.01)
                                ->suffix('%')
                                ->reactive()
                                ->afterStateHydrated(function ($state, callable $set) {
                                    if ($state === null || $state === '') {
                                        $set('tax', 0);
                                        return;
                                    }
                                    $set('tax', (float) $state);
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.tax');
                                    $livewire->resetErrorBag('data.items.*.tax');
                                    if ($state === null || $state === '' || ! is_numeric($state)) {
                                        return;
                                    }
                                    self::updateLineTotalDisplay($set, $get);
                                    self::recalcTotals($set, $get);
                                })
                                ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : $state)
                                ->visible(fn (callable $get) => ($get('../../discount_mode') ?? 'percent') !== 'amount'),

                            TextInput::make('tax_amount')
                                ->label('Tax (PKR)')
                                ->inputMode('decimal')
                                ->rule('numeric')
                                ->extraInputAttributes(['data-line-field' => 'tax_amount'])
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(function (callable $get) {
                                    $qty = (float) ($get('quantity') ?? 0);
                                    $unit = (float) ($get('unit_price') ?? 0);
                                    $lineSubtotal = $qty * $unit;

                                    return max(0, $lineSubtotal);
                                })
                                ->validationMessages([
                                    'max' => 'Tax amount cannot be greater than the line subtotal.',
                                ])
                                ->step(0.01)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $livewire->resetValidation('data.items.*.tax_amount');
                                    $livewire->resetErrorBag('data.items.*.tax_amount');
                                    if ($state === null || $state === '' || ! is_numeric($state)) {
                                        return;
                                    }
                                    $lineSubtotal = (float) ($get('line_subtotal') ?? 0);
                                    $discountAmount = (float) ($get('discount_amount') ?? 0);
                                    $taxableAmount = $lineSubtotal - $discountAmount;
                                    $amount = round(max(0, (float) ($state ?? 0)), 2);
                                    $rate = $taxableAmount > 0 ? ($amount / $taxableAmount) * 100 : 0;

                                    $set('tax', $rate);
                                    self::updateLineTotalDisplay($set, $get);
                                    self::recalcTotals($set, $get);
                                })
                                ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : $state)
                                ->visible(fn (callable $get) => ($get('../../discount_mode') ?? 'percent') === 'amount'),

                            /* -------- LINE TOTAL -------- */
                            TextInput::make('line_total')
                                ->label('Line Total')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->extraInputAttributes(['data-line-field' => 'line_total'])
                                ->default(0),
                            Hidden::make('line_subtotal')
                                ->default(0)
                                ->dehydrated(false),
                        ])
                        ->columns(4)
                        ->defaultItems(1)
                        ->minItems(1)
                        ->collapsible()
                        ->itemLabel('Item')
                        ->itemNumbers()
                        ->addActionLabel('Add Item')
                        ->reorderable(false)
                        ->deletable(true)
                        ->afterStateHydrated(function (callable $set, callable $get) {
                            $items = $get('items') ?? [];
                            $discountMode = $get('discount_mode') ?? 'percent';

                            foreach ($items as &$item) {
                                $qty = (float) ($item['quantity'] ?? 0);
                                $unit = (float) ($item['unit_price'] ?? 0);
                                $lineSubtotal = (float) ($item['line_subtotal'] ?? ($qty * $unit));
                                if ($lineSubtotal <= 0) {
                                    $lineSubtotal = (float) ($item['line_total'] ?? 0);
                                }

                                $discountRate = (float) ($item['discount'] ?? 0);
                                $taxRate = (float) ($item['tax'] ?? 0);

                                if ($discountMode === 'amount') {
                                    $discountAmount = (float) ($item['discount_amount'] ?? 0);
                                    if ($discountAmount <= 0 && $discountRate > 0) {
                                        $discountAmount = $lineSubtotal * ($discountRate / 100);
                                    }
                                    $discountAmount = min(max(0, $discountAmount), $lineSubtotal);
                                    $discountRate = $lineSubtotal > 0 ? ($discountAmount / $lineSubtotal) * 100 : 0;

                                    $taxableAmount = max(0, $lineSubtotal - $discountAmount);
                                    $taxAmount = (float) ($item['tax_amount'] ?? 0);
                                    if ($taxAmount <= 0 && $taxRate > 0) {
                                        $taxAmount = $taxableAmount * ($taxRate / 100);
                                    }
                                    $taxAmount = min(max(0, $taxAmount), $lineSubtotal);
                                    $taxRate = $taxableAmount > 0 ? ($taxAmount / $taxableAmount) * 100 : 0;
                                } else {
                                    $discountRate = max(0, min(100, $discountRate));
                                    $taxRate = max(0, min(100, $taxRate));
                                    $discountAmount = $lineSubtotal * ($discountRate / 100);
                                    $taxableAmount = max(0, $lineSubtotal - $discountAmount);
                                    $taxAmount = $taxableAmount * ($taxRate / 100);
                                }

                                $item['line_subtotal'] = $lineSubtotal;
                                $item['discount'] = round(min(100, $discountRate), 2);
                                $item['tax'] = round(min(100, $taxRate), 2);
                                $item['discount_amount'] = round($discountAmount, 2);
                                $item['tax_amount'] = round($taxAmount, 2);
                                $item['line_total'] = round($taxableAmount + $taxAmount, 2);
                            }

                            $set('items', $items);
                            self::recalcTotals($set, $get);
                        })
                        ->afterStateUpdated(fn (callable $set, callable $get) =>
                        self::recalcTotals($set, $get)
                        ),
                ]),

            /* ===========================
             * SUMMARY
             * =========================== */
            Section::make('Summary')
                ->extraAttributes(['class' => 'blue-section'])
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Placeholder::make('subtotal_display')
                        ->label('Subtotal')
                        ->extraAttributes(['data-summary' => 'subtotal'])
                        ->content(fn (callable $get) =>
                        'PKR ' . number_format((float) ($get('subtotal') ?? 0), 2)
                        ),

                    Placeholder::make('total_discount_display')
                        ->label('Discount')
                        ->extraAttributes(['data-summary' => 'discount'])
                        ->content(fn (callable $get) =>
                        'PKR ' . number_format((float) ($get('total_discount') ?? 0), 2)
                        ),

                    Placeholder::make('total_tax_display')
                        ->label('Tax')
                        ->extraAttributes(['data-summary' => 'tax'])
                        ->content(fn (callable $get) =>
                        'PKR ' . number_format((float) ($get('total_tax') ?? 0), 2)
                        ),

                    Placeholder::make('total_amount_display')
                        ->label('Total Amount')
                        ->extraAttributes(['data-summary' => 'total'])
                        ->content(fn (callable $get) =>
                        'PKR ' . number_format((float) ($get('total_amount') ?? 0), 2)
                        ),

                    Hidden::make('subtotal')->default(0)->dehydrated(),
                    Hidden::make('total_discount')->default(0)->dehydrated(),
                    Hidden::make('total_tax')->default(0)->dehydrated(),
                    Hidden::make('total_amount')->default(0)->dehydrated(),
                ]),

            /* ===========================
             * NOTES
             * =========================== */
            Section::make('Notes')
                ->extraAttributes(['class' => 'blue-section'])
                ->columnSpanFull()
                ->schema([
                    Textarea::make('notes')
                        ->maxLength(255)
                        ->rows(3),
                ]),
            View::make('filament.forms.line-calc-script'),
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

        $discountMode = $get($rootPrefix . 'discount_mode') ?? 'percent';
        $subtotal = 0.0;
        $totalDiscount = 0.0;
        $totalTax = 0.0;

        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $unit = (float) ($item['unit_price'] ?? 0);
            $lineSubtotal = (float) ($item['line_subtotal'] ?? ($qty * $unit));
            if ($lineSubtotal <= 0) {
                $lineSubtotal = (float) ($item['line_total'] ?? 0);
            }

            $subtotal += $lineSubtotal;

            if ($discountMode === 'amount') {
                $discountAmountInput = (float) ($item['discount_amount'] ?? 0);
                $discountAmount = min(max(0, $discountAmountInput), $lineSubtotal);
                $taxableAmount = max(0, $lineSubtotal - $discountAmount);

                $taxAmountInput = (float) ($item['tax_amount'] ?? 0);
                $taxAmount = min(max(0, $taxAmountInput), $lineSubtotal);
            } else {
                $discountRate = max(0, min(100, (float) ($item['discount'] ?? 0)));
                $taxRate = max(0, min(100, (float) ($item['tax'] ?? 0)));

                $discountAmount = $lineSubtotal * ($discountRate / 100);
                $taxableAmount = max(0, $lineSubtotal - $discountAmount);
                $taxAmount = $taxableAmount * ($taxRate / 100);
            }

            $totalDiscount += $discountAmount;
            $totalTax += $taxAmount;
        }

        $set($rootPrefix . 'subtotal', $subtotal);
        $set($rootPrefix . 'total_discount', $totalDiscount);
        $set($rootPrefix . 'total_tax', $totalTax);
        $set($rootPrefix . 'total_amount', $subtotal - $totalDiscount + $totalTax);
    }

    private static function updateLineTotalDisplay(callable $set, callable $get): void
    {
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $qty = max(1, (float) ($get('quantity') ?? 1));
        $lineSubtotal = $unitPrice * $qty;
        $discountRate = (float) ($get('discount') ?? 0);
        $taxRate = (float) ($get('tax') ?? 0);
        $discountAmountInput = (float) ($get('discount_amount') ?? 0);
        $taxAmountInput = (float) ($get('tax_amount') ?? 0);
        $discountMode = $get('../../discount_mode') ?? 'percent';

        $set('line_subtotal', $lineSubtotal);

        if ($discountMode === 'amount' && $lineSubtotal > 0) {
            $discountAmountInput = min(max(0, $discountAmountInput), $lineSubtotal);
            $discountRate = ($discountAmountInput / $lineSubtotal) * 100;
            $set('discount', round(min(100, $discountRate), 2));
        }

        $discountAmount = $lineSubtotal * ($discountRate / 100);
        $taxableLine = max(0, $lineSubtotal - $discountAmount);

        if ($discountMode === 'amount' && $taxableLine > 0) {
            $taxAmountInput = min(max(0, $taxAmountInput), $lineSubtotal);
            $taxRate = ($taxAmountInput / $taxableLine) * 100;
            $set('tax', round(min(100, $taxRate), 2));
        }

        $taxAmount = $discountMode === 'amount'
            ? $taxAmountInput
            : ($taxableLine * ($taxRate / 100));
        $lineTotal = $taxableLine + $taxAmount;

        $set('line_total', round($lineTotal, 2));
    }
}
