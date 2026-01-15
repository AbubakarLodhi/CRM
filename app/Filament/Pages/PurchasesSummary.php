<?php

namespace App\Filament\Pages;


use App\Models\Purchase;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PurchasesSummary extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;
    protected static string|\UnitEnum|null $navigationGroup = 'Reportings';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Purchases Summary';
    protected static ?string $navigationLabel = 'Purchases Summary';

    protected string $view = 'filament.pages.purchases-summary';

    /* ============================================================
     |  TABLE
     ============================================================ */

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->query(function () {
                $user = Filament::auth()->user();

                $merchantId = match (true) {
                    $user instanceof \App\Models\Merchant => $user->id,
                    $user instanceof \App\Models\User     => $user->merchant_id,
                    default                               => null,
                };

                if (! $merchantId) {
                    return Purchase::query()->whereRaw('1 = 0');
                }

                if ($user instanceof \App\Models\Merchant) {
                    return Purchase::query()
                        ->with(['merchant', 'business', 'branch', 'items'])
                        ->where('merchant_id', $merchantId);
                }
                return Purchase::query()
                    ->with(['merchant', 'business', 'branch', 'items'])
                    ->where('merchant_id', $merchantId)
                    ->whereHas('business.users', fn ($q) =>
                    $q->where('users.id', $user->id)
                    )
                    ->whereHas('branch.users', fn ($q) =>
                    $q->where('users.id', $user->id)
                    );
            })

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
                    ->toggleable()
                    ->limit(30)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable()
                    ->limit(30)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
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
            ->paginated([10,25, 50, 100])
            ->defaultSort('purchase_date', 'desc');
    }

    /* ============================================================
     |  IMPORTANT: FILTERED QUERY WITHOUT PAGINATION
     ============================================================ */

    protected function getFilteredTableQueryWithoutPagination(): Builder
    {
        $query = clone $this->getFilteredTableQuery();

        // 🔥 THIS IS THE KEY FIX
        $query->getQuery()->limit = null;
        $query->getQuery()->offset = null;

        return $query;
    }

    /* ============================================================
     |  STATS (FILTER-AWARE, UNPAGINATED)
     ============================================================ */
    public function getPurchaseStats(): array
    {
        $filteredQuery = $this->getFilteredTableQueryWithoutPagination();

        $totalPurchases = (clone $filteredQuery)->count();

        $purchaseIds = (clone $filteredQuery)->select('purchases.id');

        // ✅ NEW: item rows count
        $totalItemLines = DB::table('purchase_items')
            ->whereIn('purchase_id', $purchaseIds)
            ->count();

        // ✅ EXISTING: quantity sum
        $totalItemQuantity = DB::table('purchase_items')
            ->whereIn('purchase_id', $purchaseIds)
            ->sum('quantity');

        $totalAmount   = (clone $filteredQuery)->sum('total_amount');
        $totalDiscount = (clone $filteredQuery)->sum('discount');
        $totalTax      = (clone $filteredQuery)->sum('tax');
        $totalSubtotal = (clone $filteredQuery)->sum('subtotal');

        $avgPurchase = $totalPurchases > 0
            ? $totalAmount / $totalPurchases
            : 0;

        return [
            'total_purchases'      => (int) $totalPurchases,
            'total_items_count'    => (int) $totalItemLines,     // 👈 rows
            'total_items_quantity' => (float) $totalItemQuantity, // 👈 quantity
            'total_amount'         => (float) $totalAmount,
            'total_discount'       => (float) $totalDiscount,
            'total_tax'            => (float) $totalTax,
            'total_subtotal'       => (float) $totalSubtotal,
            'avg_purchase'         => round($avgPurchase, 2),
        ];
    }

}
