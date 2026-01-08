<?php

namespace App\Filament\Resources\Brands\Tables;

use App\Filament\Resources\BrandModels\BrandModelResource;
use App\Filament\Resources\Brands\BrandsResource;
use App\Models\BrandCategory;
use App\Models\BrandModel;
use App\Models\Category;
use App\Models\PermissionModule;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        $user  = Filament::auth()->user();
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };
        return $table
            /**
             * ✅ Each row is a BrandCategory
             */
            ->query(
                BrandCategory::query()
                    ->with(['brand.logo', 'category'])
                    ->when(
                        $merchantId,
                        fn ($q) => $q->where('merchant_id', $merchantId)
                    )
                    ->when(
                        request()->filled('category_id'),
                        fn ($q) => $q->where('category_id', request('category_id'))
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
                    ->getStateUsing(fn($record) => $record->brand?->logo
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
                 * Assigned date
                 */
                TextColumn::make('created_at')
                    ->label('Assigned At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        $user = Filament::auth()->user();

                        return Category::query()
                            // ✅ ONLY sub-categories
                            ->whereNotNull('parent_id')

                            // ✅ Merchant scoping
                            ->when(
                                fn ($q) => $q->where(
                                    'merchant_id',
                                    $user->merchant_id ?? $user->id
                                )
                            )

                            // Admin sees all
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    }),


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
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->tooltip('View Models')
                    ->url(fn($record) => BrandModelResource::getUrl('index', [
                        'brand_id' => $record->brand_id,
                    ])
                    )

                    // ✅ BRAND-ONLY CHECK (CORRECT)
                    ->visible(function ($record) {
                        if (! $record) {
                            return false;
                        }

                        $guard = Filament::getCurrentPanel()->getAuthGuard();
                        $user  = Auth::guard($guard)->user();

                        // 🧩 1. Module toggle
                        if (! PermissionModule::isEnabledForCurrentMerchant('brands')) {
                            return false;
                        }

                        // 🔐 2. Permission gate
                        if (! $user?->hasPermissionTo('brands.view', $guard)) {
                            return false;
                        }

                        // 🔍 3. Brand must have models
                        return BrandModel::where('brand_id', $record->brand_id)->exists();
                    }),


                /**
                 * Edit BRAND
                 */
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit Brand')
                    ->url(fn($record) => BrandsResource::getUrl('edit', [
                        'record' => $record->brand_id,
                    ])
                    )
                    ->visible(fn() => auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('brands.update')
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
                    ->action(fn($record) => $record->delete())
                    ->visible(fn() => auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('brands.delete')
                    ),


            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Remove Category')
                        ->action(fn($records) => $records->each->delete())
                        ->visible(fn() => auth(Filament::getCurrentPanel()->getAuthGuard())
                            ->user()?->hasPermissionTo('brands.delete')
                        ),
                ]),
            ]);
    }
}
