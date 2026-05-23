<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('id')
                            ->label('Order ID'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (string $state) => ucfirst($state))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('sale.sale_no')
                            ->label('Sale Number')
                            ->url(fn ($record) => \App\Filament\Resources\Sales\SaleResource::getUrl('view', ['record' => $record->sale_id]))
                            ->openUrlInNewTab(false),

                        TextEntry::make('sale.sale_date')
                            ->label('Sale Date')
                            ->date('d/m/Y'),

                        TextEntry::make('sale.customer.name')
                            ->label('Customer'),

                        TextEntry::make('merchant.name')
                            ->label('Merchant'),

                        TextEntry::make('business.name')
                            ->label('Business'),

                        TextEntry::make('branch.name')
                            ->label('Branch'),

                        TextEntry::make('sale.total_amount')
                            ->label('Total Amount')
                            ->money('PKR')
                            ->weight('bold'),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                Section::make('Status Notes')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('status_notes')
                            ->label('Status Notes')
                            ->placeholder('No status notes available')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
