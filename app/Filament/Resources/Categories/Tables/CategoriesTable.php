<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Brands\BrandsResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\BrandCategory;
use App\Models\Category;
use App\Models\PermissionModule;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        $isSubCategoryContext = request()->filled('parent_id');

        return $table
            ->columns(
                $isSubCategoryContext
                    ? self::subCategoryColumns()
                    : self::categoryColumns()
            )
            ->defaultSort('created_at', 'desc')
            ->filters([

                ])
            ->recordActions([
                Action::make('view-subcategories')
                    ->color('secondary')
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->tooltip('View Sub-Categories')
                    ->url(fn (?Category $record) =>
                    $record
                        ? CategoryResource::getUrl('index', ['parent_id' => $record->id])
                        : null
                    )
                    ->visible(function () {
                        $guard = Filament::getCurrentPanel()->getAuthGuard();
                        $user  = Auth::guard($guard)->user();

                        // 🧩 1. Module toggle check
                        if (! PermissionModule::isEnabledForCurrentMerchant('sub_categories')) {
                            return false;
                        }

                        // 🔐 2. Permission check
                        if (! $user?->hasPermissionTo('sub_categories.view', $guard)) {
                            return false;
                        }

                        // 🧠 3. UI condition (only top-level categories)
                        return ! request()->filled('parent_id');
                    }),


                Action::make('view-brands')
                    ->color('secondary')
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->tooltip('View Brands')
                    ->url(fn (?Category $record) =>
                    $record
                        ? BrandsResource::getUrl('index', ['category_id' => $record->id])
                        : null
                    )

                    ->visible(function (?Category $record) {
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

                        // 🧠 3. Only show for sub-categories
                        if (! request()->filled('parent_id') || ! $record) {
                            return false;
                        }

                        // 🔍 4. Category must have brands
                        return BrandCategory::where('category_id', $record->id)->exists();
                    }),


                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->requiresConfirmation()
                    ->before(function (?Category $record) {
                        if (! $record) {
                            return;
                        }
                        if ($record->parent_id === null && $record->children()->exists()) {
                            Notification::make()
                                ->title('Cannot delete')
                                ->body('Please delete sub-categories first.')
                                ->danger()
                                ->send();

                            return false;
                        }
                    })
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())),

        ])->recordUrl(fn (Category $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('categories.update', Filament::getCurrentPanel()->getAuthGuard())
                ? \App\Filament\Resources\Users\UserResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ]);
    }

    protected static function categoryColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Category Name')
                ->sortable()
                ->searchable(),

            ImageColumn::make('icon')
                ->label('Icon')
                ->size(50)
                ->square()
                ->getStateUsing(fn (Category $record) =>
                $record->icon
                    ? asset('storage/' . $record->icon->photo_url)
                    : asset('images/placeholder.jpg')
                ),



        ];
    }

    protected static function subCategoryColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Sub-Category Name')
                ->sortable()
                ->searchable(),

            BadgeColumn::make('parent.name')
                ->label('Category')
                ->sortable()
                ->searchable(),


        ];
    }
}
