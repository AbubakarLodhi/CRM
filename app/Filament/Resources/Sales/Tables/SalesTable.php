<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
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

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('sale_no')
                    ->label('Sale No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sale_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),


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
                    ->getStateUsing(function (Sale $record) {
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
                    ->getStateUsing(function (Sale $record) {
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
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('payment_type')
                    ->label('Payment')
                    ->badge()
                    ->color(fn ($state) => $state === 'credit' ? 'warning' : 'success')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),

                BadgeColumn::make('return_status')
                    ->label('Return')
                    ->colors(['success'])
                    ->getStateUsing(fn (Sale $record) =>
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
            ->filters([

                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer')
                    ->searchable()
                    ->preload(),

            ])
            ->recordUrl(fn (Sale $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('sales.update', Filament::getCurrentPanel()->getAuthGuard())
                ? SaleResource::getUrl('edit', ['record' => $record])
                : null
            )
            ->recordActions([
                ViewAction::make()
                    ->color('info')
                    ->label('')
                    ->tooltip('View')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('sales.view', Filament::getCurrentPanel()->getAuthGuard())
                    ),

                Action::make('invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->label(' ')
                    ->tooltip('Invoice')
                    ->url(fn ($record) => route('invoices.show', [
                        'type' => 'sale',
                        'id'   => $record->id,
                    ])),
                    //->openUrlInNewTab(),


                Action::make('return_sale')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->label(' ')
                    ->tooltip('Return Sale')
                    ->modalHeading('Return Sale')
                    ->modalWidth('7xl')
                    ->form(fn (Sale $record) => self::returnForm($record))
                    ->action(function (Sale $record, array $data) {
                        \App\Services\SaleReturnService::createReturn($record, $data);
                    })
                    ->visible(fn (Sale $record) => ! $record->returns()->exists()),

                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('sales.update', Filament::getCurrentPanel()->getAuthGuard())
                    ),

                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('sales.delete', Filament::getCurrentPanel()->getAuthGuard())
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () =>
                        auth(Filament::getCurrentPanel()->getAuthGuard())
                            ->user()?->hasPermissionTo('sales.delete', Filament::getCurrentPanel()->getAuthGuard())
                        ),
                ]),
            ])
            ->defaultSort('sale_date', 'desc');
    }


    public static function returnForm(Sale $sale): array
    {
        // Make sure relationships are loaded
        $sale->loadMissing('items.product', 'items.variants.variant');

        $summary = self::prefillReturnSummary($sale);

        return [
            DatePicker::make('return_date')
                ->default(now())
                ->required(),

            Textarea::make('reason'),

            Repeater::make('items')
                ->default(
                    $sale->items->map(function ($item) {

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
                            'sale_item_id' => $item->id,
                            'product_name' => $item->product?->name ?? 'Product',
                            'variant_name' => $variantLabel,
                            'max_quantity' => $item->quantity,
                            'quantity' => $item->quantity, // Prefill sold quantity
                            'unit_price' => $item->unit_price,
                            'discount' => $item->discount ?? 0,
                            'tax' => $item->tax ?? 0,
                        ];
                    })->toArray()
                )
                ->afterStateHydrated(fn (callable $set, callable $get) => self::recalcReturnTotals($set, $get))
                ->afterStateUpdated(fn (callable $set, callable $get) => self::recalcReturnTotals($set, $get))
                ->schema([

                    Hidden::make('sale_item_id'),

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
                        ->rules([
                            fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                $max = $get('max_quantity');

                                if ($value > $max) {
                                    $fail("Return quantity cannot be greater than sold quantity ({$max}).");
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

    private static function prefillReturnSummary(Sale $sale): array
    {
        $subtotal = 0.0;
        $totalDiscount = 0.0;
        $totalTax = 0.0;

        foreach ($sale->items as $item) {
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
