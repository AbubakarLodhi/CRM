<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
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

                ImageColumn::make('product_image')
                    ->label('Image')
                    ->size(40)
                    ->square()
                    ->getStateUsing(fn (Product $record) =>
                    $record->productImage
                        ? asset('storage/' . $record->productImage->photo_url)
                        : null
                    )
                    ->defaultImageUrl(asset('images/product-placeholder.png')),

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

//                TextColumn::make('business.name')
//                    ->label('Business')
//                    ->sortable()
//                    ->searchable(),

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
                Action::make('view-variants')
                    ->icon('heroicon-o-squares-2x2')
                    ->label('')
                    ->tooltip('View Variants')
                    ->url(fn ($record) =>
                    \App\Filament\Resources\ProductVariants\ProductVariantResource::getUrl('index', [
                        'product_id'      => $record->id,
                        'brand_model_id'  => $record->brand_model_id,
                        'brand_id'        => $record->brand_id,
                        'category_id'     => $record->category_id,
                    ])
                    )
                    ->openUrlInNewTab(false),

                EditAction::make()
                    ->label('')
                    ->tooltip('Edit'),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete'),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
