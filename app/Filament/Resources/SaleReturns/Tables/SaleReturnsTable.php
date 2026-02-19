<?php

namespace App\Filament\Resources\SaleReturns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SaleReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                BadgeColumn::make('merchant.name')
                    ->label('Merchant')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sale.sale_no')
                    ->label('Sale No.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('return_no')
                    ->searchable(),
                TextColumn::make('return_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_discount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_tax')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
