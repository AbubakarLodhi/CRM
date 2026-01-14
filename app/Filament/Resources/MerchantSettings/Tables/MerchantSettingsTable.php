<?php

namespace App\Filament\Resources\MerchantSettings\Tables;

use App\Models\MerchantSetting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MerchantSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('logo_path')
                    ->searchable(),
                TextColumn::make('primary_color')
                    ->searchable(),
                TextColumn::make('secondary_color')
                    ->searchable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('merchant.name')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordUrl(fn (MerchantSetting $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('merchant_settings.update', Filament::getCurrentPanel()->getAuthGuard())
                ? \App\Filament\Resources\Users\UserResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )

            ->recordActions([
                EditAction::make()
                    ->color('warning')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('merchant_settings.edit', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('merchant_settings.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ]);
    }
}
