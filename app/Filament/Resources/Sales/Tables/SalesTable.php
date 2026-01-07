<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
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
                    ->searchable(),

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

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('discount')
                    ->label('Discount')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tax')
                    ->label('Tax')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

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
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('sales.view', Filament::getCurrentPanel()->getAuthGuard())),
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('sales.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('sales.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('sales.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ])
            ->defaultSort('sale_date', 'desc');
    }
}
