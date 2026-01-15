<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('phone')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email address')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('occupation')
                    ->label('Occupation')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Show merchant name instead of ID
                BadgeColumn::make('merchant.name')
                    ->label('Merchant')
                    ->color('primary')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                // Show reference customer name instead of ID
                TextColumn::make('reference')
                    ->label('Reference Customer')
                    ->limit(30)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([

            ])
            ->recordUrl(fn (Customer $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('customers.update', Filament::getCurrentPanel()->getAuthGuard())
                ? CustomerResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )

            ->recordActions([
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('customers.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('customers.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('customers.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
