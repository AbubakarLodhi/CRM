<?php

namespace App\Filament\Resources\ProductVariants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Variant')
                    ->searchable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('selling_price')
                    ->money('PKR') // change currency if needed
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
//                    ->visible(fn () =>
//                    auth(Filament::getCurrentPanel()->getAuthGuard())
//                        ->user()?->hasPermissionTo('products.update', Filament::getCurrentPanel()->getAuthGuard())
//                    ),
                DeleteAction::make()
//                    ->visible(fn () =>
//                    auth(Filament::getCurrentPanel()->getAuthGuard())
//                        ->user()?->hasPermissionTo('products.delete', Filament::getCurrentPanel()->getAuthGuard())
//                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
//                        ->visible(fn () =>
//                        auth(Filament::getCurrentPanel()->getAuthGuard())
//                            ->user()?->hasPermissionTo('products.delete', Filament::getCurrentPanel()->getAuthGuard())
//                        ),
                ]),
            ]);
    }
}
