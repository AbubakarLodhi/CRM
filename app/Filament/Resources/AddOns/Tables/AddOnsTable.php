<?php

namespace App\Filament\Resources\AddOns\Tables;

use App\Filament\Resources\AddOns\AddOnResource;
use App\Models\AddOn;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddOnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) =>
            $query->with(['brandModel', 'merchant'])
            )

            ->columns([
                TextColumn::make('name')
                    ->label('Add-On Name')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->sortable(),

                TextColumn::make('brandModel.name')
                    ->label('Brand Model'),

                TextColumn::make('merchant.name')
                    ->label('Merchant'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (AddOn $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('addons.update', Filament::getCurrentPanel()->getAuthGuard())
                ? AddOnResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )

            ->recordActions([
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()
                        ?->hasPermissionTo('addons.update', Filament::getCurrentPanel()->getAuthGuard())
                    ),

                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()
                        ?->hasPermissionTo('addons.delete', Filament::getCurrentPanel()->getAuthGuard())
                    ),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () =>
                        auth(Filament::getCurrentPanel()->getAuthGuard())
                            ->user()
                            ?->hasPermissionTo('addons.delete', Filament::getCurrentPanel()->getAuthGuard())
                        ),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
