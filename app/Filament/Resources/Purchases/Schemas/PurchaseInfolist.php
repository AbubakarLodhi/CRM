<?php

namespace App\Filament\Resources\Purchases\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Purchase Information')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('purchase_no')
                            ->label('Purchase Number'),

                        TextEntry::make('purchase_date')
                            ->label('Purchase Date')
                            ->date('d/m/Y'),

                        TextEntry::make('merchant.name')
                            ->label('Merchant'),

                        TextEntry::make('businesses')
                            ->label('Business')
                            ->getStateUsing(function ($record) {
                                $names = $record->items()
                                    ->join('businesses', 'businesses.id', '=', 'purchase_items.business_id')
                                    ->select('businesses.name')
                                    ->distinct()
                                    ->pluck('name')
                                    ->filter();

                                return $names->isNotEmpty() ? $names->implode(', ') : '-';
                            }),

                        TextEntry::make('branches')
                            ->label('Branch')
                            ->getStateUsing(function ($record) {
                                $names = $record->items()
                                    ->join('branches', 'branches.id', '=', 'purchase_items.branch_id')
                                    ->select('branches.name')
                                    ->distinct()
                                    ->pluck('name')
                                    ->filter();

                                return $names->isNotEmpty() ? $names->implode(', ') : '-';
                            }),

                        TextEntry::make('created_by_display')
                            ->label('Created By')
                            ->getStateUsing(fn ($record) => $record->createdBy?->name ?: ($record->merchant?->name ?: '-')),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                Section::make('Purchase Items')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),

                                TextEntry::make('product.sku')
                                    ->label('SKU'),

                                TextEntry::make('quantity')
                                    ->label('Quantity'),

                                TextEntry::make('unit_price')
                                    ->label('Unit Price')
                                    ->money('PKR'),

                                TextEntry::make('discount')
                                    ->label('Discount (%)')
                                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 2) . '%'),

                                TextEntry::make('tax')
                                    ->label('Tax (%)')
                                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 2) . '%'),

                                TextEntry::make('line_total')
                                    ->label('Line Total')
                                    ->money('PKR'),
                            ])
                            ->columns(7),
                    ]),

                Section::make('Summary')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('PKR'),

                        TextEntry::make('discount')
                            ->label('Discount')
                            ->money('PKR')
                            ->getStateUsing(function ($record) {
                                return $record->items->sum(function ($item) {
                                    $lineTotal = (float) ($item->line_total ?? 0);
                                    $discountRate = (float) ($item->discount ?? 0);
                                    return $lineTotal * ($discountRate / 100);
                                });
                            }),

                        TextEntry::make('tax')
                            ->label('Tax')
                            ->money('PKR')
                            ->getStateUsing(function ($record) {
                                return $record->items->sum(function ($item) {
                                    $lineTotal = (float) ($item->line_total ?? 0);
                                    $discountRate = (float) ($item->discount ?? 0);
                                    $taxRate = (float) ($item->tax ?? 0);
                                    $discountAmount = $lineTotal * ($discountRate / 100);
                                    $taxableAmount = $lineTotal - $discountAmount;
                                    return $taxableAmount * ($taxRate / 100);
                                });
                            }),

                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money('PKR')
                            ->weight('bold'),
                    ]),

                Section::make('Notes')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('No notes available')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
