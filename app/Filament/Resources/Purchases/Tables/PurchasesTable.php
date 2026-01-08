<?php

namespace App\Filament\Resources\Purchases\Tables;

use App\Models\Branch;
use App\Models\Business;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchase_no')
                    ->label('Purchase No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchase_date')
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
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
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
                /**
                 * 🏢 BUSINESS FILTER
                 */
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

                        $query = Business::query()
                            ->where('merchant_id', $merchantId);

                        // STAFF → assigned businesses only
                        if ($user instanceof \App\Models\User) {
                            $query->whereHas('users', fn ($q) =>
                            $q->where('users.id', $user->id)
                            );
                        }

                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->query(fn (Builder $query, array $data) =>
                    filled($data['value'])
                        ? $query->where('business_id', $data['value'])
                        : null
                    ),

                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->searchable()
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

                        $businessId = $livewire->getTableFilterState('business_id')['value'] ?? null;

                        $query = \App\Models\Branch::query()
                            ->where('merchant_id', $merchantId);

                        if ($businessId) {
                            $query->where('business_id', $businessId);
                        }

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
                    ->query(fn (Builder $query, array $data) =>
                    filled($data['value'])
                        ? $query->where('branch_id', $data['value'])
                        : null
                    ),




            ])

            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
