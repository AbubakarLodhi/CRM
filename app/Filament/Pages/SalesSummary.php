<?php

namespace App\Filament\Pages;

use App\Models\Purchase;
use App\Models\Sale;
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

class SalesSummary extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;
    protected static string|\UnitEnum|null $navigationGroup = 'Reportings';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Sales Summary';
    protected static ?string $navigationLabel = 'Sales Summary';

    protected string $view = 'filament.pages.sales-summary';

    /* ============================================================
     |  TABLE (UNCHANGED)
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
                    return Sale::query()->whereRaw('1 = 0');
                }

                $query = Sale::query()
                    ->where('merchant_id', $merchantId)
                    ->with([
                        'merchant',
                        'items.business',
                        'items.branch',
                    ]);

                if ($user instanceof \App\Models\User) {
                    $query
                        ->whereHas('items.business.users', fn ($q) =>
                        $q->where('users.id', $user->id)
                        )
                        ->whereHas('items.branch.users', fn ($q) =>
                        $q->where('users.id', $user->id)
                        );
                }

                return $query;
            })
            ->columns([
                TextColumn::make('sale_no')->label('Sale No.')->searchable()->sortable(),

                TextColumn::make('sale_date')->label('Date')->date('d/m/Y')->sortable(),

                TextColumn::make('customer.name')->label('Customer')->searchable()->sortable()->limit(30),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->toggleable()
                    ->limit(30)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('business.name')->label('Business')->searchable()->sortable()->toggleable()->limit(30),

                TextColumn::make('branch.name')->label('Branch')->searchable()->sortable()->toggleable()->limit(30),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('subtotal')->money('USD')->sortable()->toggleable(),
                TextColumn::make('discount')->money('USD')->sortable()->toggleable(),
                TextColumn::make('tax')->money('USD')->sortable()->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->filters([


                SelectFilter::make('customer_id')
                    ->label('Customer')
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

                        return \App\Models\Customer::query()
                            ->where('merchant_id', $merchantId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(fn (Builder $query, array $data) =>
                    filled($data['value'])
                        ? $query->where('customer_id', $data['value'])
                        : null
                    ),


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
            ->defaultSort('sale_date', 'desc');
    }

    /* ============================================================
     |  FILTERED QUERY WITHOUT PAGINATION
     ============================================================ */

    protected function getFilteredTableQueryWithoutPagination(): Builder
    {
        $query = clone $this->getFilteredTableQuery();
        $query->getQuery()->limit = null;
        $query->getQuery()->offset = null;
        return $query;
    }

    /* ============================================================
     |  STATS (SALES PERSPECTIVE)
     ============================================================ */

    public function getSalesStats(): array
    {
        $filteredQuery = $this->getFilteredTableQueryWithoutPagination();

        $totalSales = (clone $filteredQuery)->count();

        $saleIds = (clone $filteredQuery)->select('sales.id');

        // 🔹 Item rows (line items)
        $totalItemLines = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->count();

        // 🔹 Actual quantity sold
        $totalQuantitySold = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->sum('quantity');

        $totalAmount   = (clone $filteredQuery)->sum('total_amount');
        $totalDiscount = (clone $filteredQuery)->sum('discount');
        $totalTax      = (clone $filteredQuery)->sum('tax');
        $totalSubtotal = (clone $filteredQuery)->sum('subtotal');

        $avgSale = $totalSales > 0 ? $totalAmount / $totalSales : 0;

        return [
            'total_sales'        => (int) $totalSales,
            'total_items_count'  => (int) $totalItemLines,
            'total_quantity'     => (float) $totalQuantitySold,
            'total_amount'       => (float) $totalAmount,
            'total_discount'     => (float) $totalDiscount,
            'total_tax'          => (float) $totalTax,
            'total_subtotal'     => (float) $totalSubtotal,
            'avg_sale'           => round($avgSale, 2),
        ];
    }
}
