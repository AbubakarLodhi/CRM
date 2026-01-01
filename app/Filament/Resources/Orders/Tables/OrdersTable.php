<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale.sale_no')
                    ->label('Sale No.')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => \App\Filament\Resources\Sales\SaleResource::getUrl('view', ['record' => $record->sale_id]))
                    ->openUrlInNewTab(false),

                TextColumn::make('sale.sale_date')
                    ->label('Sale Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('sale.customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sale.total_amount')
                    ->label('Total Amount')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('business.name')
                    ->label('Business')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status_notes')
                    ->label('Status Notes')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->label('Status'),

                SelectFilter::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->label('Merchant')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Filament::auth()->user() instanceof \App\Models\Admin),

                SelectFilter::make('business_id')
                    ->relationship('business', 'name')
                    ->label('Business')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Branch')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->color('info')
                    ->label('')
                    ->tooltip('View')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('orders.view', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
