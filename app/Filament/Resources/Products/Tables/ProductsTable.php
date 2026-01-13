<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\PermissionModule;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
                    ->size(50)
                    ->square()
                    ->getStateUsing(fn (Product $record) =>
                    $record->productImage
                        ? asset('storage/' . $record->productImage->photo_url)
                        : asset('images/placeholder.jpg')
                    ),





                TextColumn::make('sku')
                    ->searchable(),

                TextColumn::make('type')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge(),

                TextColumn::make('unit')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge(),

//                TextColumn::make('selling_price')
//                    ->money()
//                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

//                TextColumn::make('business.name')
//                    ->label('Business')
//                    ->sortable()
//                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->toggleable(),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('view-variants')
                    ->color('secondary')
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->tooltip('View Variants')
                    ->url(fn ($record) =>
                    \App\Filament\Resources\ProductVariants\ProductVariantResource::getUrl('index', [
                        'product_id'     => $record->id,
                        'brand_model_id' => $record->brand_model_id,
                        'brand_id'       => $record->brand_id,
                        'category_id'    => $record->category_id,
                    ])
                    )
                    // ✅ SHOW ONLY IF VARIANTS EXIST FOR THIS PRODUCT
                    ->visible(function ($record) {
                        if (! $record) {
                            return false;
                        }

                        $guard = Filament::getCurrentPanel()->getAuthGuard();
                        $user  = Auth::guard($guard)->user();

                        if (! PermissionModule::isEnabledForCurrentMerchant('products_variants')) {
                            return false;
                        }
                        if (! $user?->hasPermissionTo('products_variants.view', $guard)) {
                            return false;
                        }

                        // 📦 3. Variants must exist for this product
                        return ProductVariant::where('product_id', $record->id)->exists();
                    })
                    ->openUrlInNewTab(false),
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('products.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('products.delete', Filament::getCurrentPanel()->getAuthGuard())),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
