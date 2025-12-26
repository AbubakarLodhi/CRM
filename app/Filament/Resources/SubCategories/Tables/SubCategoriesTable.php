<?php

namespace App\Filament\Resources\SubCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Sub Category name')
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
                SelectFilter::make('parent_id')
                    ->label('Parent Category')
                    ->relationship(
                        'parent',
                        'name',
                        fn ($query) => $query->whereNull('parent_id')
                    )
                    ->searchable()
                    ->preload()



            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('categories.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
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
