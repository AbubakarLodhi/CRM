<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('sale.sale_no')
                    ->label('Sale No.')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Order $record) =>
                    SaleResource::getUrl('view', ['record' => $record->sale_id])
                    ),

                TextColumn::make('sale.sale_date')
                    ->label('Sale Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('sale.customer.name')
                    ->label('Customer')
                    ->limit(30)
                    ->sortable()
                    ->searchable(),

                /* ===========================
                 * BUSINESS (via sale items)
                 * =========================== */
                TextColumn::make('businesses')
                    ->label('Business')
                    ->getStateUsing(fn (Order $record) =>
                    $record->sale
                        ?->items
                        ?->pluck('business.name')
                        ->unique()
                        ->join(', ')
                    )
                    ->toggleable()
                    ->searchable(),

                /* ===========================
                 * BRANCH (via sale items)
                 * =========================== */
                TextColumn::make('branches')
                    ->label('Branch')
                    ->getStateUsing(fn (Order $record) =>
                    $record->sale
                        ?->items
                        ?->pluck('branch.name')
                        ->unique()
                        ->join(', ')
                    )
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'completed'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('sale.total_amount')
                    ->label('Total Amount')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                /* -------- STATUS -------- */
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'completed'  => 'Completed',
                        'cancelled'  => 'Cancelled',
                    ]),

                /* -------- BUSINESS (via sale items) -------- */
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

                        if ($user instanceof \App\Models\User) {
                            $query->whereHas('users', fn ($q) =>
                            $q->where('users.id', $user->id)
                            );
                        }

                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->query(fn (Builder $query, array $data) =>
                    filled($data['value'])
                        ? $query->whereHas('sale.items', fn ($q) =>
                    $q->where('business_id', $data['value'])
                    )
                        : null
                    ),

                /* -------- BRANCH (via sale items) -------- */
                SelectFilter::make('branch_id')
                    ->label('Branch')
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

                        $query = \App\Models\Branch::query()
                            ->where('merchant_id', $merchantId);

                        if ($user instanceof \App\Models\User) {
                            $query->whereHas('users', fn ($q) =>
                            $q->where('users.id', $user->id)
                            );
                        }

                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->query(fn (Builder $query, array $data) =>
                    filled($data['value'])
                        ? $query->whereHas('sale.items', fn ($q) =>
                    $q->where('branch_id', $data['value'])
                    )
                        : null
                    ),
            ])
            ->recordUrl(fn (Order $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('orders.view', Filament::getCurrentPanel()->getAuthGuard())
                ? OrderResource::getUrl('view', ['record' => $record])
                : null
            )
            ->recordActions([
                ViewAction::make()
                    ->tooltip('View')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()
                        ?->hasPermissionTo('orders.view', Filament::getCurrentPanel()->getAuthGuard())
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
