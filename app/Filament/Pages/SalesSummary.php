<?php

namespace App\Filament\Pages;

use App\Models\Admin;
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
            ->query(
                Sale::query()
                    ->with(['merchant', 'business', 'branch', 'customer', 'items'])
                    ->when(
                        $user && ! $user instanceof Admin,
                        fn (Builder $query) => $query->where('merchant_id', $user->id)
                    )
            )
            ->columns([
                TextColumn::make('sale_no')->label('Sale No.')->searchable()->sortable(),

                TextColumn::make('sale_date')->label('Date')->date('d/m/Y')->sortable(),

                TextColumn::make('customer.name')->label('Customer')->searchable()->sortable(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable()
                    ->toggleable(fn () => $user instanceof Admin),

                TextColumn::make('business.name')->label('Business')->searchable()->sortable()->toggleable(),

                TextColumn::make('branch.name')->label('Branch')->searchable()->sortable()->toggleable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('subtotal')->money('USD')->sortable(),
                TextColumn::make('discount')->money('USD')->sortable()->toggleable(),
                TextColumn::make('tax')->money('USD')->sortable()->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => $user instanceof Admin),

                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('business_id')
                    ->label('Business')
                    ->relationship(
                        'business',
                        'name',
                        modifyQueryUsing: function (Builder $query) use ($user) {
                            if ($user && ! $user instanceof Admin) {
                                $query->where('merchant_id', $user->id);
                            }
                        }
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship(
                        'branch',
                        'name',
                        modifyQueryUsing: function (Builder $query) use ($user) {
                            if ($user && ! $user instanceof Admin) {
                                $query->where('merchant_id', $user->id);
                            }
                        }
                    )
                    ->searchable()
                    ->preload(),

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
