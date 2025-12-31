<?php

namespace App\Filament\Resources\BrandModels\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BrandModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Model Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand.name')
                    ->label('Brand Name')
                    ->sortable()
                    ->searchable(),


                TextColumn::make('brand.merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->searchable(),

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
                SelectFilter::make('merchant_id')
                    ->label('Merchant')
                    ->relationship('brand.merchant', 'name')
                    ->searchable()
                    ->preload(),



            ])
            ->recordActions([
                Action::make('view-products')
                    ->color('secondary')
                    ->icon('heroicon-o-cube')
                    ->label('')
                    ->tooltip('View Products')
                    ->url(fn ($record) =>
                   ProductResource::getUrl('index', [
                        'brand_model_id' => $record->id,
                        'brand_id'       => $record->brand_id,
                    ])
                    )

                    // ✅ SHOW ONLY IF PRODUCTS EXIST FOR THIS MODEL
                    ->visible(fn ($record) =>
                    Product::where('brand_model_id', $record->id)->exists()
                    )
                    ->openUrlInNewTab(false),

                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ]);
    }
}
