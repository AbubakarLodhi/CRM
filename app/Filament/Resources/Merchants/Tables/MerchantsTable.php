<?php

namespace App\Filament\Resources\Merchants\Tables;

use App\Models\Branch;
use App\Models\Merchant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MerchantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('address_line_1')
                    ->searchable(),
                TextColumn::make('address_line_2')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('website')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
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
            ])
            ->filters([
                //
                SelectFilter::make('is_active')
                    ->label('Active')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        Merchant::STATUS_PENDING => 'Pending',
                        Merchant::STATUS_VERIFIED => 'Verified',
                        Merchant::STATUS_REJECTED => 'Rejected',
                    ])
                    ->label('Status'),
                SelectFilter::make('city')
                    ->label('City')
                    ->options(
                        Merchant::distinct()
                            ->pluck('city', 'city')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('merchants.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('merchants.delete', Filament::getCurrentPanel()->getAuthGuard()))
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('merchants.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ]);
    }
}
