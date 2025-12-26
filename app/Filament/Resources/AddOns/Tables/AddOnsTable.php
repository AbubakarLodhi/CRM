<?php

namespace App\Filament\Resources\AddOns\Tables;

use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\BrandModel;
use App\Models\Merchant;

class AddOnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Add-On Name')->searchable(),
                TextColumn::make('price')->label('Price')->sortable(),
                TextColumn::make('model.name')->label('Brand Model')->sortable()->searchable(),
                TextColumn::make('merchant.name')->label('Merchant')->sortable()->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

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
