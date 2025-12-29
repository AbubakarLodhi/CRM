<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Brands\BrandsResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->recordActions([
                Action::make('view-subcategories')
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->tooltip('View Sub-Categories')
                    ->url(fn (?Category $record) =>
                    $record
                        ? CategoryResource::getUrl('index', ['parent_id' => $record->id])
                        : null
                    )
                    ->visible(fn () => ! request()->filled('parent_id')),

                Action::make('view-brands')
                    ->icon('heroicon-o-building-storefront')
                    ->label('')
                    ->tooltip('View Brands')
                    ->url(fn (?Category $record) =>
                    $record
                        ? BrandsResource::getUrl('index', ['category_id' => $record->id])
                        : null
                    )
                    ->visible(fn () => request()->filled('parent_id')),

                EditAction::make()
                    ->label('')
                    ->tooltip('Edit'),
                DeleteAction::make()
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
                    }),

        ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
                ->size(36)
                ->square()
                ->getStateUsing(fn (Category $record) =>
                $record->icon
                    ? asset('storage/' . $record->icon->photo_url)
                    : null
                )
                ->defaultImageUrl(asset('images/category-placeholder.png')),

            TextColumn::make('merchant.name')
                ->label('Merchant')
                ->sortable()
                ->searchable(),
        ];
    }

    protected static function subCategoryColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Sub-Category Name')
                ->sortable()
                ->searchable(),

            TextColumn::make('parent.name')
                ->label('Category')
                ->sortable()
                ->searchable(),

            TextColumn::make('merchant.name')
                ->label('Merchant')
                ->sortable()
                ->searchable(),
        ];
    }
}
