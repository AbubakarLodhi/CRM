<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\RepeatableEntry;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sale Information')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('sale_no')
                            ->label('Sale Number'),

                        TextEntry::make('sale_date')
                            ->label('Sale Date')
                            ->date('d/m/Y'),

                        TextEntry::make('customer.name')
                            ->label('Customer'),

                        TextEntry::make('merchant.name')
                            ->label('Merchant'),

                        TextEntry::make('business.name')
                            ->label('Business'),

                        TextEntry::make('branch.name')
                            ->label('Branch'),

                        TextEntry::make('createdBy.name')
                            ->label('Created By'),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                Section::make('Sale Items')
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
                                    ->money('USD'),

                                TextEntry::make('line_total')
                                    ->label('Line Total')
                                    ->money('USD'),
                            ])
                            ->columns(5),
                    ]),

                Section::make('Summary')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('USD'),

                        TextEntry::make('discount')
                            ->label('Discount')
                            ->money('USD'),

                        TextEntry::make('tax')
                            ->label('Tax')
                            ->money('USD'),

                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money('USD')
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
