<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\AssetResource;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Merchant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return $table
            ->columns([
                TextColumn::make('asset_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('assetType.name')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('location')
                    ->label('Location')
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('condition')
                    ->label('Condition')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('purchase_cost')
                    ->label('Purchase Cost')
                    ->money('PKR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('current_value')
                    ->label('Current Value')
                    ->money('PKR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('purchase_date')
                    ->label('Purchased')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('warranty_expiry')
                    ->label('Warranty')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('asset_type_id')
                    ->label('Asset Type')
                    ->options(fn (): array => self::assetTypeFilterOptions())
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(AssetStatus::options()),

                SelectFilter::make('condition')
                    ->label('Condition')
                    ->options(AssetCondition::options()),

                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('assets.view', $guard)),

                Action::make('preview')
                    ->icon('heroicon-s-document-text')
                    ->color('gray')
                    ->label(' ')
                    ->tooltip('Preview')
                    ->url(fn (Asset $record): string => route('assets.preview', ['id' => $record->id]))
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('assets.view', $guard)),

                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('assets.update', $guard)),

                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('assets.delete', $guard)),
            ])
            ->recordUrl(fn (Asset $record) => auth($guard)->user()?->hasPermissionTo('assets.view', $guard)
                ? AssetResource::getUrl('view', ['record' => $record])
                : null)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth($guard)->user()?->hasPermissionTo('assets.delete', $guard)),
                ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function assetTypeFilterOptions(): array
    {
        $user = Filament::auth()->user();
        $merchantId = match (true) {
            $user instanceof Merchant => $user->id,
            $user instanceof User => $user->merchant_id,
            default => null,
        };

        if (! $merchantId) {
            return [];
        }

        return AssetType::query()
            ->where('merchant_id', $merchantId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
