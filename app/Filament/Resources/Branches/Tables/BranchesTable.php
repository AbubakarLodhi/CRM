<?php

namespace App\Filament\Resources\Branches\Tables;

use App\Models\Branch;
use App\Models\Merchant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ])
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('business.name')
                    ->label('Business')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
                SelectFilter::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->label('Merchant')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('business_id')
                    ->relationship('business', 'name')
                    ->label('Businesses')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        Branch::STATUS_PENDING => 'Pending',
                        Branch::STATUS_VERIFIED => 'Verified',
                        Branch::STATUS_REJECTED => 'Rejected',
                    ])
                    ->label('Status')
            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('branches.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('branches.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('branches.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ]);
    }
}
