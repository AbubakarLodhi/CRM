<?php

namespace App\Filament\Pages;

use App\Filament\Exports\SalesSummaryExport;
use App\Models\Sale;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;




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
     |  TABLE (UNCHANGED – SALE LEVEL)
     ============================================================ */

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->query(function () use ($user) {
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
                    ->with(['items.business', 'items.branch']);

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
                TextColumn::make('merchant.name')->label('Merchant')->toggleable()->searchable()->sortable(),

                BadgeColumn::make('branches')
                    ->label('Branch')
                    ->colors(['primary'])
                    ->getStateUsing(function ($record) {
                        return $record->items
                            ->pluck('branch.name')
                            ->filter()
                            ->unique()
                            ->values()
                            ->toArray();
                    })
                    ->formatStateUsing(function ($state) {

                        // ✅ Normalize (Filament-safe)
                        if (empty($state)) {
                            return ['-'];
                        }

                        if (is_string($state)) {
                            return $state;
                        }

                        if (! is_array($state)) {
                            return ['-'];
                        }

                        // ✅ Show max 2 badges
                        if (count($state) <= 2) {
                            return $state;
                        }

                        return array_merge(
                            array_slice($state, 0, 2),
                            ['+' . (count($state) - 2) . ' more']
                        );
                    })
                    ->toggleable(),


                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('subtotal')->money('USD')->toggleable(),
                TextColumn::make('discount')->money('USD')->toggleable(),
                TextColumn::make('tax')->money('USD')->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->weight('bold')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name'),



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

                        return $query->orderBy('name')->pluck('name', 'id')->toArray();
                    })
                    ->query(fn (Builder $query, array $data) =>
                    filled($data['value'])
                        ? $query->whereHas('items', fn ($q) =>
                    $q->where('branch_id', $data['value'])
                    )
                        : null
                    ),

            ])
            ->paginated([10, 25, 50, 100])
            ->defaultSort('sale_date', 'desc');
    }

    /* ============================================================
     |  FILTERED QUERY (NO PAGINATION)
     ============================================================ */

    protected function getFilteredTableQueryWithoutPagination(): Builder
    {
        $query = clone $this->getFilteredTableQuery();
        $query->getQuery()->limit = null;
        $query->getQuery()->offset = null;
        return $query;
    }

    /* ============================================================
     |  STATS (VARIANT-BASED — MATCHES YOUR SQL)
     ============================================================ */

    public function getSalesStats(): array
    {
        $filteredQuery = $this->getFilteredTableQueryWithoutPagination();

        // Sale IDs in scope
        $saleIds = (clone $filteredQuery)->pluck('sales.id');

        // -----------------------------
        // TOTAL SALES
        // -----------------------------
        $totalSales = $saleIds->count();

        // -----------------------------
        // ✅ ITEM COUNT (MATCHES TABLE)
        // SUM of sale_items rows
        // -----------------------------
        $totalItemLines = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->count();

        // -----------------------------
        // ✅ QUANTITY SOLD (VARIANT BASED)
        // -----------------------------
        $totalQuantitySold = DB::table('sale_item_variants as sv')
            ->join('sale_items as si', 'si.id', '=', 'sv.sale_item_id')
            ->whereIn('si.sale_id', $saleIds)
            ->sum('sv.quantity');

        // -----------------------------
        // MONETARY TOTALS (SALE LEVEL)
        // -----------------------------
        $totalAmount   = (clone $filteredQuery)->sum('total_amount');
        $totalDiscount = (clone $filteredQuery)->sum('discount');
        $totalTax      = (clone $filteredQuery)->sum('tax');
        $totalSubtotal = (clone $filteredQuery)->sum('subtotal');

        $avgSale = $totalSales > 0 ? $totalAmount / $totalSales : 0;

        // 🚨 HEADERS EXACTLY AS REQUIRED
        return [
            'total_sales'        => (int) $totalSales,
            'total_items_count'  => (int) $totalItemLines, // ✅ NOW MATCHES TABLE
            'total_quantity'     => (float) $totalQuantitySold,
            'total_amount'       => (float) $totalAmount,
            'total_discount'     => (float) $totalDiscount,
            'total_tax'          => (float) $totalTax,
            'total_subtotal'     => (float) $totalSubtotal,
            'avg_sale'           => round($avgSale, 2),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export to Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('reports.view', Filament::getCurrentPanel()->getAuthGuard()))
                ->color('success')
                ->action(function () {

                    $baseQuery = $this->getFilteredTableQueryWithoutPagination();

                    $exportQuery = (clone $baseQuery)
                        ->withCount('items')
                        ->with([
                            'merchant',
                            'customer',
                            'items.branch',
                        ]);

                    // Sale IDs from SAME filtered dataset
                    $saleIds = (clone $baseQuery)->select('sales.id');

                    $totals = [
                        'items_count' => (int) DB::table('sale_items')
                            ->whereIn('sale_id', $saleIds)
                            ->count(),

                        'quantity' => (float) DB::table('sale_item_variants as sv')
                            ->join('sale_items as si', 'si.id', '=', 'sv.sale_item_id')
                            ->whereIn('si.sale_id', $saleIds)
                            ->sum('sv.quantity'),

                        'subtotal' => (float) (clone $baseQuery)->sum('subtotal'),
                        'discount' => (float) (clone $baseQuery)->sum('discount'),
                        'tax'      => (float) (clone $baseQuery)->sum('tax'),
                        'total'    => (float) (clone $baseQuery)->sum('total_amount'),
                    ];

                    return Excel::download(
                        new SalesSummaryExport($exportQuery, $totals),
                        'sales-summary-' . now()->format('Y-m-d_H-i-s') . '.xlsx'
                    );
                }),
        ];
    }


}
