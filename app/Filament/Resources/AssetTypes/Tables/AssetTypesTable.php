<?php

namespace App\Filament\Resources\AssetTypes\Tables;

use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\AssetTypes\AssetTypeResource;
use App\Models\AssetType;
use App\Models\PermissionModule;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AssetTypesTable
{
    public static function configure(Table $table): Table
    {
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Type Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('assets_count')
                    ->label('Assets')
                    ->counts('assets')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                Action::make('view-assets')
                    ->color('secondary')
                    ->icon('heroicon-s-cube')
                    ->label('')
                    ->tooltip('View Assets')
                    ->url(fn (AssetType $record): string => AssetResource::getUrl('index', ['asset_type_id' => $record->id]))
                    ->visible(fn (): bool => PermissionModule::isEnabledForCurrentMerchant('assets')
                        && auth($guard)->user()?->hasPermissionTo('assets.view', $guard)),

                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('asset_types.update', $guard)),

                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('asset_types.delete', $guard)),
            ])
            ->recordUrl(fn (AssetType $record) => auth($guard)->user()?->hasPermissionTo('asset_types.update', $guard)
                ? AssetTypeResource::getUrl('edit', ['record' => $record])
                : null)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth($guard)->user()?->hasPermissionTo('asset_types.delete', $guard)),
                ]),
            ]);
    }
}
