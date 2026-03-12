<?php

namespace App\Filament\Resources\Brands\Tables;

use App\Filament\Resources\BrandModels\BrandModelResource;
use App\Filament\Resources\Brands\BrandsResource;
use App\Models\Brand;
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
use Illuminate\Support\Facades\DB;

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
            /*
             |--------------------------------------------------------------------------
             | QUERY (ONE ROW = ONE BRAND)
             |--------------------------------------------------------------------------
             */
            ->query(
                Brand::query()
                    ->with(['logo', 'categories'])
                    ->when(
                        $merchantId,
                        fn ($q) => $q->where('merchant_id', $merchantId)
                    )
            )

            /*
             |--------------------------------------------------------------------------
             | COLUMNS
             |--------------------------------------------------------------------------
             */
            ->columns([
                // Brand name
                TextColumn::make('name')
                    ->label('Brand')
                    ->limit(30)
                    ->searchable()
                    ->sortable(),

                // Brand logo
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->size(40)
                    ->square()
                    ->getStateUsing(fn ($record) =>
                    $record->logo
                        ? asset('storage/' . $record->logo->photo_url)
                        : asset('images/placeholder.jpg')
                    ),


                // All categories in ONE column
                BadgeColumn::make('categories')
                    ->label('Categories')
                    ->getStateUsing(fn ($record) =>
                    $record->categories
                        ->pluck('name')
                        ->values()
                        ->toArray()
                    )
                    ->limit(30)
                    ->separator(', ')
                    ->colors(['primary'])
                    ->getStateUsing(function (Brand $record) {
                        $names = $record->categories->pluck('name');

                        $visible = $names->take(2);
                        $hiddenCount = $names->count() - $visible->count();

                        if ($hiddenCount > 0) {
                            $visible->push('+' . $hiddenCount);
                        }

                        return $visible->toArray();
                    }),

                // Created at
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])

            /*
             |--------------------------------------------------------------------------
             | FILTERS
             |--------------------------------------------------------------------------
             */
            ->filters([
                SelectFilter::make('categories')
                    ->label('Categories')
                    ->multiple() // ✅ MULTI SELECT
                    ->searchable()
                    ->preload()
                    ->options(fn () =>
                    Category::query()
                        ->whereNotNull('parent_id')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->query(function ($query, array $data) {
                        if (empty($data['values'])) {
                            return;
                        }

                        $query->whereHas('categories', function ($q) use ($data) {
                            $q->whereIn('categories.id', $data['values']);
                        });
                    }),


            ])

            /*
             |--------------------------------------------------------------------------
             | ROW ACTIONS
             |--------------------------------------------------------------------------
             */
            ->recordActions([
                // View models
                Action::make('view-models')
                    ->color('secondary')
                    ->icon('heroicon-s-eye')
                    ->label('')
                    ->tooltip('View Models')
                    ->url(fn ($record) => BrandModelResource::getUrl('index', [
                        'brand_id' => $record->id,
                    ]))
                    ->visible(function ($record) use ($guard) {
                        $user = Auth::guard($guard)->user();

                        if (! PermissionModule::isEnabledForCurrentMerchant('brands')) {
                            return false;
                        }

                        if (! $user?->hasPermissionTo('brands.view', $guard)) {
                            return false;
                        }

                        return BrandModel::where('brand_id', $record->id)->exists();
                    }),

                // Edit brand
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () =>
                    auth($guard)
                        ->user()
                        ?->hasPermissionTo('brands.update', $guard)
                    )
                    ->url(fn ($record) => BrandsResource::getUrl('edit', [
                        'record' => $record->id,
                    ])),

                // Remove ALL category assignments (not the brand itself)
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->modalHeading('Delete Brand')
                    ->modalDescription('Are you sure you want to permanently delete this brand? This action cannot be undone.')
                    ->requiresConfirmation()
                    ->action(function (Brand $record) {
                        DB::transaction(function () use ($record) {

                            // 1️⃣ Delete logo
                            $record->logo()?->delete();

                            // 2️⃣ Delete pivot rows
                            $record->categories()->detach();

                            // 3️⃣ Delete brand
                            $record->delete();
                        });
                    })
                    ->visible(fn () =>
                    auth($guard)
                        ->user()
                        ?->hasPermissionTo('brands.delete', $guard)
                    ),

            ])

            /*
             |--------------------------------------------------------------------------
             | ROW CLICK
             |--------------------------------------------------------------------------
             */
            ->recordUrl(fn ($record) =>
            auth($guard)
                ->user()
                ?->hasPermissionTo('brands.update', $guard)
                ? BrandsResource::getUrl('edit', ['record' => $record->id])
                : null
            )

            /*
             |--------------------------------------------------------------------------
             | BULK ACTIONS
             |--------------------------------------------------------------------------
             */
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(fn ($records) =>
                        DB::transaction(function () use ($records) {
                            $records->each(function (Brand $record) {
                                $record->logo()?->delete();
                                $record->categories()->detach();
                                $record->delete();
                            });
                        })
                        )
                        ->visible(fn () =>
                        auth($guard)
                            ->user()
                            ?->hasPermissionTo('brands.delete', $guard)
                        ),

                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }
}
