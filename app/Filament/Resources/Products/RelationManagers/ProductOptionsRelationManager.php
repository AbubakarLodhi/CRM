<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductOptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Key'),
                TextColumn::make('display_name'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('products.create', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->actions([
//                EditAction::make()
//                    ->url(fn ($record) => route(
//                        'filament.admin.resources.product-options.edit',
//                        $record
//                    )),

                DeleteAction::make()
                    ->color('danger')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('products.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ]);
    }
}

