<?php

namespace App\Filament\Resources\Purchases\Tables;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Purchase;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /* -----------------------------
                 * BASIC INFO
                 * ----------------------------- */
                TextColumn::make('purchase_no')
                    ->label('Purchase No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchase_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->limit(30)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->limit(30)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                /* -----------------------------
                 * BUSINESS (FROM ITEMS)
                 * ----------------------------- */
                TextColumn::make('businesses')
                    ->label('Business')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(function (Purchase $record) {
                        $names = $record->items()
                            ->join('businesses', 'businesses.id', '=', 'purchase_items.business_id')
                            ->select('businesses.name')
                            ->distinct()
                            ->pluck('name');

                        $visible = $names->take(2);
                        $hidden  = $names->count() - $visible->count();

                        if ($hidden > 0) {
                            $visible->push('+' . $hidden);
                        }

                        return $visible->toArray();
                    })
                    ->sortable(false)
                    ->toggleable(),

                /* -----------------------------
                 * BRANCH (FROM ITEMS)
                 * ----------------------------- */
                TextColumn::make('branches')
                    ->label('Branch')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function (Purchase $record) {
                        $names = $record->items()
                            ->join('branches', 'branches.id', '=', 'purchase_items.branch_id')
                            ->select('branches.name')
                            ->distinct()
                            ->pluck('name');

                        $visible = $names->take(2);
                        $hidden  = $names->count() - $visible->count();

                        if ($hidden > 0) {
                            $visible->push('+' . $hidden);
                        }

                        return $visible->toArray();
                    })
                    ->sortable(false)
                    ->toggleable(),

                /* -----------------------------
                 * TOTALS
                 * ----------------------------- */
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('PKR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('discount')
                    ->label('Discount')
                    ->money('PKR')
                    ->getStateUsing(function (Purchase $record) {
                        return $record->items->sum(function ($item) {
                            $lineTotal = (float) ($item->line_total ?? 0);
                            $discountRate = (float) ($item->discount ?? 0);
                            return $lineTotal * ($discountRate / 100);
                        });
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tax')
                    ->label('Tax')
                    ->money('PKR')
                    ->getStateUsing(function (Purchase $record) {
                        return $record->items->sum(function ($item) {
                            $lineTotal = (float) ($item->line_total ?? 0);
                            $discountRate = (float) ($item->discount ?? 0);
                            $taxRate = (float) ($item->tax ?? 0);
                            $discountAmount = $lineTotal * ($discountRate / 100);
                            $taxableAmount = $lineTotal - $discountAmount;
                            return $taxableAmount * ($taxRate / 100);
                        });
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('PKR')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('payment_type')
                    ->label('Payment')
                    ->badge()
                    ->color(fn ($state) => $state === 'credit' ? 'warning' : 'success')
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('return_status')
                    ->label('Return')
                    ->colors(['success'])
                    ->getStateUsing(fn (Purchase $record) =>
                        $record->returns()->exists() ? 'Returned' : '-'
                    )
                    ->toggleable(),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            /* ===========================
             * FILTERS
             * =========================== */
            ->filters([

                /* -------- BUSINESS FILTER -------- */
                SelectFilter::make('business_id')
                    ->label('Business')
                    ->options(function () {
                        $user = Filament::auth()->user();

                        $merchantId = match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                               => null,
                        };

                        if (! $merchantId) {
                            return [];
                        }

                        $query = Business::query()
                            ->where('merchant_id', $merchantId);

                        if ($user instanceof \App\Models\User) {
                            $query->whereHas('users', fn ($q) =>
                            $q->where('users.id', $user->id)
                            );
                        }

                        return $query
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }

                        $query->whereHas('items', fn ($q) =>
                        $q->where('purchase_items.business_id', $data['value'])
                        );
                    }),

                /* -------- BRANCH FILTER -------- */
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->searchable()
                    ->options(function ($livewire) {

                        $user = Filament::auth()->user();

                        $merchantId = match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                               => null,
                        };

                        if (! $merchantId) {
                            return [];
                        }

                        $businessId = $livewire->getTableFilterState('business_id')['value'] ?? null;

                        $query = Branch::query()
                            ->where('merchant_id', $merchantId);

                        if ($businessId) {
                            $query->where('business_id', $businessId);
                        }

                        if ($user instanceof \App\Models\User) {
                            $query->whereHas('users', fn ($q) =>
                            $q->where('users.id', $user->id)
                            );
                        }

                        return $query
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }

                        $query->whereHas('items', fn ($q) =>
                        $q->where('purchase_items.branch_id', $data['value'])
                        );
                    }),

                SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->searchable()
                    ->options(function () {
                        $user = Filament::auth()->user();

                        $merchantId = match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                               => null,
                        };

                        if (! $merchantId) {
                            return [];
                        }

                        return Vendor::query()
                            ->where('merchant_id', $merchantId)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }

                        $query->where('vendor_id', $data['value']);
                    }),

                SelectFilter::make('payment_type')
                    ->label('Payment Type')
                    ->options([
                        'cash' => 'Cash',
                        'credit' => 'Credit',
                    ]),

                Filter::make('purchase_date_range')
                    ->label('Purchase Date')
                    ->schema([
                        DatePicker::make('from_date')
                            ->label('From Date'),
                        DatePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '<=', $date),
                            );
                    }),
            ])

            /* ===========================
             * RECORD ACTIONS
             * =========================== */
            ->recordUrl(fn (Purchase $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('purchases.update', Filament::getCurrentPanel()->getAuthGuard())
                ? PurchaseResource::getUrl('edit', ['record' => $record])
                : null
            )

            ->recordActions([
                ViewAction::make()
                    ->color('info')
                    ->label(' ')
                    ->tooltip('View')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('purchases.view', Filament::getCurrentPanel()->getAuthGuard())
                    ),

                Action::make('invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->label(' ')
                    ->tooltip('Invoice')
                    ->url(fn (Purchase $record): string => route('invoices.show', [
                        'type' => 'purchase',
                        'id' => $record->id,
                    ])),

                Action::make('return_purchase')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->label(' ')
                    ->color('danger')
                    ->tooltip('Return Purchase')
                    ->modalHeading('Return Purchase')
                    ->modalWidth('7xl')
                    ->form(fn (Purchase $record) => self::returnForm($record))
                    ->action(function (Purchase $record, array $data) {
                        \App\Services\PurchaseReturnService::createReturn($record, $data);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Purchase returned')
                            ->send();
                    })
                    ->visible(fn (Purchase $record) => ! $record->returns()->exists()),

                EditAction::make()
                    ->color('warning')
                    ->label(' ')
                    ->tooltip('Edit')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('purchases.update', Filament::getCurrentPanel()->getAuthGuard())
                    ),

                DeleteAction::make()
                    ->color('danger')
                    ->label(' ')
                    ->tooltip('Delete')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('purchases.delete', Filament::getCurrentPanel()->getAuthGuard())
                    ),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () =>
                        auth(Filament::getCurrentPanel()->getAuthGuard())
                            ->user()?->hasPermissionTo('purchases.delete', Filament::getCurrentPanel()->getAuthGuard())
                        ),
                ]),
            ])

            ->defaultSort('purchase_date', 'desc');
    }

    public static function returnForm(Purchase $purchase): array
    {
        $purchase->loadMissing('items.product', 'items.variants.variant');

        $summary = self::prefillReturnSummary($purchase);

        return [
            DatePicker::make('return_date')
                ->default(now())
                ->required(),

            Textarea::make('reason'),

            Repeater::make('items')
                ->default(
                    $purchase->items->map(function ($item) {

                        $variant = $item->variants->first();
                        $variantModel = $variant?->variant;

                        $variantLabel = $variantModel
                            ? (
                                $variantModel->name
                                ?? $variantModel->sku
                                ?? $variantModel->option_values
                                ?? substr($variantModel->id, 0, 8)
                            )
                            : '-';

                        return [
                            'purchase_item_id' => $item->id,
                            'product_name' => $item->product?->name ?? 'Product',
                            'variant_name' => $variantLabel,
                            'max_quantity' => $item->quantity,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'discount' => $item->discount ?? 0,
                            'tax' => $item->tax ?? 0,
                        ];
                    })->toArray()
                )
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->afterStateHydrated(fn (callable $set, callable $get) => self::recalcReturnTotals($set, $get))
                ->afterStateUpdated(fn (callable $set, callable $get) => self::recalcReturnTotals($set, $get))
                ->schema([
                    Hidden::make('purchase_item_id'),
                    Hidden::make('max_quantity'),
                    Hidden::make('discount'),
                    Hidden::make('tax'),

                    Placeholder::make('product_name')
                        ->label('Product'),

                    Placeholder::make('variant_name')
                        ->label('Variant'),

                    TextInput::make('quantity')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(fn ($get) => $get('max_quantity'))
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->rules([
                            fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                $max = $get('max_quantity');
                                if ($value > $max) {
                                    $fail("Return quantity cannot be greater than purchased quantity ({$max}).");
                                }
                            },
                        ]),

                    TextInput::make('unit_price')
                        ->disabled(),
                ]),

            Section::make('Summary')
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Placeholder::make('subtotal_display')
                        ->label('Subtotal')
                        ->live()
                        ->extraAttributes(['data-summary' => 'subtotal'])
                        ->content(fn (callable $get) =>
                        'PKR ' . number_format((float) ($get('subtotal') ?? 0), 2)
                        ),

                    Placeholder::make('total_discount_display')
                        ->label('Discount')
                        ->live()
                        ->extraAttributes(['data-summary' => 'discount'])
                        ->content(fn (callable $get) =>
                        'PKR ' . number_format((float) ($get('total_discount') ?? 0), 2)
                        ),

                    Placeholder::make('total_tax_display')
                        ->label('Tax')
                        ->live()
                        ->extraAttributes(['data-summary' => 'tax'])
                        ->content(fn (callable $get) =>
                        'PKR ' . number_format((float) ($get('total_tax') ?? 0), 2)
                        ),

                    Placeholder::make('total_amount_display')
                        ->label('Total Amount')
                        ->live()
                        ->extraAttributes(['data-summary' => 'total'])
                        ->content(fn (callable $get) =>
                        'PKR ' . number_format((float) ($get('total_amount') ?? 0), 2)
                        ),

                    Hidden::make('subtotal')->default($summary['subtotal'])->dehydrated(),
                    Hidden::make('total_discount')->default($summary['total_discount'])->dehydrated(),
                    Hidden::make('total_tax')->default($summary['total_tax'])->dehydrated(),
                    Hidden::make('total_amount')->default($summary['total_amount'])->dehydrated(),
                ]),
        ];
    }

    private static function recalcReturnTotals(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];

        $subtotal = 0.0;
        $totalDiscount = 0.0;
        $totalTax = 0.0;

        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $unit = (float) ($item['unit_price'] ?? 0);
            $lineSubtotal = $qty * $unit;

            $discountRate = (float) ($item['discount'] ?? 0);
            $taxRate = (float) ($item['tax'] ?? 0);

            $discountAmount = $lineSubtotal * ($discountRate / 100);
            $taxableAmount = max(0, $lineSubtotal - $discountAmount);
            $taxAmount = $taxableAmount * ($taxRate / 100);

            $subtotal += $lineSubtotal;
            $totalDiscount += $discountAmount;
            $totalTax += $taxAmount;
        }

        $set('subtotal', round($subtotal, 2));
        $set('total_discount', round($totalDiscount, 2));
        $set('total_tax', round($totalTax, 2));
        $set('total_amount', round($subtotal - $totalDiscount + $totalTax, 2));
    }

    private static function prefillReturnSummary(Purchase $purchase): array
    {
        $subtotal = 0.0;
        $totalDiscount = 0.0;
        $totalTax = 0.0;

        foreach ($purchase->items as $item) {
            $qty = (float) ($item->quantity ?? 0);
            $unit = (float) ($item->unit_price ?? 0);
            $lineSubtotal = $qty * $unit;

            $discountRate = (float) ($item->discount ?? 0);
            $taxRate = (float) ($item->tax ?? 0);

            $discountAmount = $lineSubtotal * ($discountRate / 100);
            $taxableAmount = max(0, $lineSubtotal - $discountAmount);
            $taxAmount = $taxableAmount * ($taxRate / 100);

            $subtotal += $lineSubtotal;
            $totalDiscount += $discountAmount;
            $totalTax += $taxAmount;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'total_discount' => round($totalDiscount, 2),
            'total_tax' => round($totalTax, 2),
            'total_amount' => round($subtotal - $totalDiscount + $totalTax, 2),
        ];
    }
}
