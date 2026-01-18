<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Product;
use Filament\Actions\Action;
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
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use App\Models\Customer;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use Illuminate\Database\Eloquent\Builder;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->columnSpanFull()
                ->schema([

                    /* ---------------- SALE INFORMATION ---------------- */
                    Section::make('Sale Information')
                        ->schema([
                            TextInput::make('sale_no')
                                ->label('Sale Number')
                                ->default(fn () => 'SAL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)))
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),

                            DatePicker::make('sale_date')
                                ->label('Sale Date')
                                ->default(now())
                                ->required()
                                ->displayFormat('d/m/Y'),

                            Select::make('customer_id')
                                ->label('Customer')
                                ->relationship(
                                    'customer',
                                    'name',
                                    fn (Builder $query) => $query->where(
                                        'merchant_id',
                                        Filament::auth()->user() instanceof \App\Models\Merchant
                                            ? Filament::auth()->user()->id
                                            : Filament::auth()->user()->merchant_id
                                    )
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->suffixAction(
                                    Action::make('createCustomer')
                                        ->icon('heroicon-o-plus')
                                        ->tooltip('Create Customer')
                                        ->modalHeading('Create Customer')
                                        ->modalSubmitActionLabel('Create')
                                        ->modalWidth('lg')
                                        ->model(Customer::class)
                                        ->form(CustomerForm::components())
                                        ->action(fn (array $data, callable $set) =>
                                        $set('customer_id', Customer::create($data)->id)
                                        )
                                )
                                ->afterStateUpdated(fn ($_, $__, $___, $livewire) => (
                                    $livewire->resetValidation('data.customer_id') ||
                                    $livewire->resetErrorBag('data.customer_id')
                                )),

                            Select::make('business_id')
                                ->label('Business')
                                ->relationship(
                                    'business',
                                    'name',
                                    function (Builder $query) {
                                        $user = Filament::auth()->user();
                                        $query->where(
                                            'merchant_id',
                                            $user instanceof \App\Models\Merchant
                                                ? $user->id
                                                : $user->merchant_id
                                        );

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
                                ->live()
                                ->afterStateUpdated(function (callable $set, $livewire) {
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
                                        $query
                                            ->where('business_id', $get('business_id'))
                                            ->where(
                                                'merchant_id',
                                                $user instanceof \App\Models\Merchant
                                                    ? $user->id
                                                    : $user->merchant_id
                                            );

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
                                ->live()
                                ->afterStateUpdated(fn ($_, $__, $___, $livewire) => (
                                    $livewire->resetValidation('data.branch_id') ||
                                    $livewire->resetErrorBag('data.branch_id')
                                )),

                            Hidden::make('merchant_id')
                                ->default(fn () =>
                                Filament::auth()->user() instanceof \App\Models\Merchant
                                    ? Filament::auth()->user()->id
                                    : Filament::auth()->user()->merchant_id
                                )
                                ->required(),
                        ]),

                    /* ---------------- MERCHANT LOGO (NO BOX) ---------------- */
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
            Section::make('Sale Items')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('items')
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->options(function (callable $get): array {

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
                                        });


                                    $merchantId = match (true) {
                                        $user instanceof \App\Models\Merchant => $user->id,
                                        $user instanceof \App\Models\User     => $user->merchant_id,
                                        default                               => null,
                                    };

                                    $query->where('products.merchant_id', $merchantId);



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


                                        $query->where('products.merchant_id', $user->id);


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
                                ->afterStateUpdated(function ($state, callable $set, callable $get,$livewire) {
                                    $livewire->resetValidation('data.items.*.product_id');
                                    $livewire->resetErrorBag('data.items.*.product_id');
                                    if (! $state) {
                                        return;
                                    }

                                    $product = Product::query()
                                        ->select(['id', 'selling_price'])
                                        ->find($state);

                                    if (! $product) {
                                        return;
                                    }

                                    $qty  = (float) ($get('quantity') ?? 1);
                                    $unit = (float) ($product->selling_price ?? 0);

                                    $set('unit_price', $unit);
                                    $set('line_total', $unit * $qty);

                                    SaleForm::recalcTotals($set, $get);
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
                                    // ✅ Ensure quantity is at least 1
                                    $qty = max(1, (float) ($state ?? 1));

                                    $unit = (float) ($get('unit_price') ?? 0);

                                    $set('quantity', $qty); // update state if negative
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
                                    $unit = max(0, (float) ($state ?? 0));
                                    $qty = (float) ($get('quantity') ?? 1);

                                    $set('unit_price', $unit);
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
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
        ]);
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
        $subtotal = collect($items)->sum(fn($item) => (float)($item['line_total'] ?? 0));

        $discount = (float)($get('discount') ?? 0);
        $tax = (float)($get('tax') ?? 0);

        $set('subtotal', $subtotal);
        $set('total_amount', $subtotal - $discount + $tax);
    }
}
