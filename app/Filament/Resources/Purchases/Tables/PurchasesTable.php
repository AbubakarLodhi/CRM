<?php

namespace App\Filament\Resources\Purchases\Tables;

use App\Filament\Resources\Purchases\PurchaseResource;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Purchase;
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
use Illuminate\Support\Facades\DB;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /* -----------------------------
                 * BASIC INFO
                 * ----------------------------- */
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
                    ->limit(30)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                /* -----------------------------
                 * BUSINESS (FROM ITEMS)
                 * ----------------------------- */
                TextColumn::make('businesses')
                    ->label('Business')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(function (Purchase $record) {
                        $names = $record->items()
                            ->join('businesses', 'businesses.id', '=', 'purchase_items.business_id')
                            ->select('businesses.name')
                            ->distinct()
                            ->pluck('name');

                        $visible = $names->take(2);
                        $hidden  = $names->count() - $visible->count();

                        if ($hidden > 0) {
                            $visible->push('+' . $hidden);
                        }

                        return $visible->toArray();
                    })
                    ->sortable(false)
                    ->toggleable(),

                /* -----------------------------
                 * BRANCH (FROM ITEMS)
                 * ----------------------------- */
                TextColumn::make('branches')
                    ->label('Branch')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function (Purchase $record) {
                        $names = $record->items()
                            ->join('branches', 'branches.id', '=', 'purchase_items.branch_id')
                            ->select('branches.name')
                            ->distinct()
                            ->pluck('name');

                        $visible = $names->take(2);
                        $hidden  = $names->count() - $visible->count();

                        if ($hidden > 0) {
                            $visible->push('+' . $hidden);
                        }

                        return $visible->toArray();
                    })
                    ->sortable(false)
                    ->toggleable(),

                /* -----------------------------
                 * TOTALS
                 * ----------------------------- */
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

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
                    ->weight('bold')
                    ->sortable(),

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

            /* ===========================
             * FILTERS
             * =========================== */
            ->filters([

                /* -------- BUSINESS FILTER -------- */
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
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }

                        $query->whereHas('items', fn ($q) =>
                        $q->where('purchase_items.business_id', $data['value'])
                        );
                    }),

                /* -------- BRANCH FILTER -------- */
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

                        $query = Branch::query()
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
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }

                        $query->whereHas('items', fn ($q) =>
                        $q->where('purchase_items.branch_id', $data['value'])
                        );
                    }),
            ])

            /* ===========================
             * RECORD ACTIONS
             * =========================== */
            ->recordUrl(fn (Purchase $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('purchases.update', Filament::getCurrentPanel()->getAuthGuard())
                ? PurchaseResource::getUrl('edit', ['record' => $record])
                : null
            )

            ->recordActions([
                ViewAction::make()
                    ->color('info')
                    ->tooltip('View')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('purchases.view', Filament::getCurrentPanel()->getAuthGuard())
                    ),

                EditAction::make()
                    ->color('warning')
                    ->tooltip('Edit')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('purchases.update', Filament::getCurrentPanel()->getAuthGuard())
                    ),

                DeleteAction::make()
                    ->color('danger')
                    ->tooltip('Delete')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('purchases.delete', Filament::getCurrentPanel()->getAuthGuard())
                    ),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () =>
                        auth(Filament::getCurrentPanel()->getAuthGuard())
                            ->user()?->hasPermissionTo('purchases.delete', Filament::getCurrentPanel()->getAuthGuard())
                        ),
                ]),
            ])

            ->defaultSort('purchase_date', 'desc');
    }
}
