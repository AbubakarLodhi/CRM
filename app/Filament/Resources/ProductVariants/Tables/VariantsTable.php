<?php

namespace App\Filament\Resources\ProductVariants\Tables;

use App\Filament\Resources\ProductVariants\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
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
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable()
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('selling_price')
                    ->money('PKR') // change currency if needed
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (ProductVariant $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('products_variants.update', Filament::getCurrentPanel()->getAuthGuard())
                ? ProductVariantResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )
            ->recordActions([
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('products_variants.update', Filament::getCurrentPanel()->getAuthGuard())
                    ),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('products_variants.delete', Filament::getCurrentPanel()->getAuthGuard())
                    ),
//                DeleteAction::make()
//                    ->color('danger')
//                    ->visible(fn () =>
//                    auth(Filament::getCurrentPanel()->getAuthGuard())
//                        ->user()?->hasPermissionTo('products.delete', Filament::getCurrentPanel()->getAuthGuard())
//                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () =>
                        auth(Filament::getCurrentPanel()->getAuthGuard())
                            ->user()?->hasPermissionTo('products_variants.delete', Filament::getCurrentPanel()->getAuthGuard())
                        ),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
