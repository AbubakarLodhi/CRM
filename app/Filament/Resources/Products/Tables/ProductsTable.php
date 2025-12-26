<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->searchable(),

                TextColumn::make('type')
                    ->badge(),

                TextColumn::make('unit')
                    ->badge(),

//                TextColumn::make('selling_price')
//                    ->money()
//                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('business.name')
                    ->label('Business')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->toggleable(),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
