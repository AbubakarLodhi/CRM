<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_no')
                    ->label('Expense No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('expense_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('business.name')
                    ->label('Business')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->toggleable()
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->toggleable()
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('discount')
                    ->label('Discount')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tax')
                    ->label('Tax')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([


                SelectFilter::make('business_id')
                    ->label('Business')
                    ->options(function () {
                        $user = Filament::auth()->user();

                        $merchantId = match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                               => null,
                        };

                        if (! $merchantId) {
                            return [];
                        }

                        $query = \App\Models\Business::query()
                            ->where('merchant_id', $merchantId);

                        // 🔵 Staff → assigned businesses only
                        if ($user instanceof \App\Models\User) {
                            $query->whereHas('users', fn ($q) =>
                            $q->where('users.id', $user->id)
                            );
                        }

                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query, array $data) =>
                    filled($data['value'])
                        ? $query->where('business_id', $data['value'])
                        : null
                    ),


                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(function ($livewire) {
                        $user = Filament::auth()->user();

                        $merchantId = match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                               => null,
                        };

                        if (! $merchantId) {
                            return [];
                        }

                        // ✅ Filament v3–safe way
                        $businessId = $livewire->getTableFilterState('business_id')['value'] ?? null;

                        $query = \App\Models\Branch::query()
                            ->where('merchant_id', $merchantId);

                        if ($businessId) {
                            $query->where('business_id', $businessId);
                        }

                        // 🔵 Staff → assigned branches only
                        if ($user instanceof \App\Models\User) {
                            $query->whereHas('users', fn ($q) =>
                            $q->where('users.id', $user->id)
                            );
                        }

                        return $query
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query, array $data) =>
                    filled($data['value'])
                        ? $query->where('branch_id', $data['value'])
                        : null
                    ),

            ])
            ->recordActions([
                ViewAction::make()
                    ->color('info')
                    ->label('')
                    ->tooltip('View')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('expenses.view', Filament::getCurrentPanel()->getAuthGuard())),
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('expenses.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('expenses.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('expenses.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ])
            ->defaultSort('expense_date', 'desc');
    }
}
