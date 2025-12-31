<?php

namespace App\Filament\Resources\Brands\Tables;

use App\Models\BrandCategory;
use App\Models\BrandModel;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            /**
             * ✅ Each row is a BrandCategory
             */
            ->query(
                BrandCategory::query()
                    ->with(['brand.logo', 'category', 'merchant'])
                    ->when(
                        request()->filled('category_id'),
                        fn ($query) =>
                        $query->where(
                            'category_id',
                            request('category_id')
                        )
                    )
            )

            ->columns([
                /**
                 * Brand name
                 */
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),

                /**
                 * Brand logo
                 * ❗ NO Brand type-hint here
                 */
                ImageColumn::make('brand_logo')
                    ->label('Logo')
                    ->size(40)
                    ->square()
                    ->getStateUsing(fn ($record) =>
                    $record->brand?->logo
                        ? asset('storage/' . $record->brand->logo->photo_url)
                        : asset('images/placeholder.jpg')
                    ),

                /**
                 * Single category per row
                 */
                BadgeColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                /**
                 * Merchant
                 */
                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),

                /**
                 * Assigned date
                 */
                TextColumn::make('created_at')
                    ->label('Assigned At')
                    ->dateTime()
                    ->sortable(),
            ])

            ->recordActions([
                /**
                 * View models (brand + category aware)
                 */
//                Action::make('view-models')
//                    ->icon('heroicon-o-rectangle-stack')
//                    ->label('')
//                    ->tooltip('View Models')
//                    ->url(fn ($record) =>
//                    \App\Filament\Resources\BrandModels\BrandModelResource::getUrl('index', [
//                        'brand_id'    => $record->brand_id,
//                        'category_id' => $record->category_id,
//                    ])
//                    ),
                Action::make('view-models')
                    ->color('secondary')
                    ->icon('heroicon-o-rectangle-stack')
                    ->label('')
                    ->tooltip('View Models')
                    ->url(fn ($record) =>
                    \App\Filament\Resources\BrandModels\BrandModelResource::getUrl('index', [
                        'brand_id' => $record->brand_id,
                    ])
                    )

                    // ✅ BRAND-ONLY CHECK (CORRECT)
                    ->visible(fn ($record) =>
                    BrandModel::where('brand_id', $record->brand_id)->exists()
                    ),


                /**
                 * Edit BRAND
                 */
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit Brand')
                    ->url(fn ($record) =>
                    \App\Filament\Resources\Brands\BrandsResource::getUrl('edit', [
                        'record' => $record->brand_id,
                    ])
                    )
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('categories.update')
                    ),

                /**
                 * Remove category from brand (pivot delete)
                 */
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Remove Brand')
                    ->modalHeading('Remove Brand')
                    ->modalDescription('Are you sure you want to remove this brand from this category?')
                    ->modalSubmitActionLabel('Yes, remove')
                    ->modalCancelActionLabel('Cancel')
                    ->action(fn ($record) => $record->delete())
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('categories.delete')
                    ),


            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Remove Category')
                        ->action(fn ($records) => $records->each->delete())
                        ->visible(fn () =>
                        auth(Filament::getCurrentPanel()->getAuthGuard())
                            ->user()?->hasPermissionTo('categories.delete')
                        ),
                ]),
            ]);
    }
}
