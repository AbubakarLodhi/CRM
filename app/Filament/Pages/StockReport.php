<?php

namespace App\Filament\Pages;


use App\Models\Product;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StockReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;
    protected static string|\UnitEnum|null $navigationGroup = 'Reportings';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Stock Report';
    protected static ?string $navigationLabel = 'Stock Report';

    protected string $view = 'filament.pages.stock-report';

    /* ============================================================
     |  Expressions used ONLY for table display
     ============================================================ */

    protected function purchasedExpression(string $userId): string
    {
        return "
        COALESCE(
            (
                SELECT SUM(pi.quantity)
                FROM purchase_items pi
                JOIN purchases p ON p.id = pi.purchase_id
                WHERE pi.product_id = products.id
                  AND p.branch_id IN (
                      SELECT branch_id
                      FROM branch_users
                      WHERE user_id = '{$userId}'
                  )
            ),
        0)
    ";
    }



    protected function soldExpression(string $userId): string
    {
        return "
        COALESCE(
            (
                SELECT SUM(si.quantity)
                FROM sale_items si
                JOIN sales s ON s.id = si.sale_id
                WHERE si.product_id = products.id
                  AND s.branch_id IN (
                      SELECT branch_id
                      FROM branch_users
                      WHERE user_id = '{$userId}'
                  )
            ),
        0)
    ";
    }



    protected function stockExpression(string $userId): string
    {
        return '('
            . $this->purchasedExpression($userId)
            . ' - '
            . $this->soldExpression($userId)
            . ')';
    }

    /* ============================================================
     |  TABLE (UNCHANGED STRUCTURE)
     ============================================================ */

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();
        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        return $table
            ->query(
                Product::query()
                    ->where('products.is_active', true)
                    ->where('products.track_inventory', true)
                    ->when($merchantId, fn ($q) =>
                    $q->where('products.merchant_id', $merchantId)
                    )

                    ->when($user instanceof \App\Models\User, fn ($q) =>
                    $q->whereHas('branches.users', fn ($u) =>
                    $u->where('users.id', $user->id)
                    )
                    )
                    ->select('products.*')
                    ->selectRaw(
                        $user instanceof \App\Models\User
                            ? $this->purchasedExpression($user->id) . ' as total_purchased'
                            : '
                       COALESCE(
                          (SELECT SUM(pi.quantity)
                          FROM purchase_items pi
                          WHERE pi.product_id = products.id),
                                0)
                             as total_purchased'
                    )
                    ->selectRaw(
                        $user instanceof \App\Models\User
                            ? $this->soldExpression($user->id) . ' as total_sold'
                            : '
                                      COALESCE(
                                                (SELECT SUM(si.quantity)
                                                    FROM sale_items si
                                             WHERE si.product_id = products.id),
                                                     0)
                                              as total_sold'
                    )
                    ->selectRaw(
                        $user instanceof \App\Models\User
                            ? $this->stockExpression($user->id) . ' as current_stock'
                            : '
                     (
                        COALESCE(
                              (SELECT SUM(pi.quantity)
                              FROM purchase_items pi
                              WHERE pi.product_id = products.id),
                             0)
                                   -
                        COALESCE(
                            (SELECT SUM(si.quantity)
                            FROM sale_items si
                            WHERE si.product_id = products.id),
                         0)
            )
            as current_stock
          '
                    )


            )
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->description(fn ($record) => $record->sku)
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->toggleable(),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->toggleable(),

                TextColumn::make('total_purchased')
                    ->label('Purchased')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_sold')
                    ->label('Sold')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('current_stock')
                    ->label('Stock')
                    ->badge()
                    ->icon(fn ($state) =>
                    $state <= 0
                        ? 'heroicon-o-x-circle'
                        : ($state <= 10
                        ? 'heroicon-o-exclamation-triangle'
                        : 'heroicon-o-check-circle')
                    )
                    ->color(fn ($state) =>
                    $state <= 0
                        ? 'danger'
                        : ($state <= 10
                        ? 'warning'
                        : 'success')
                    )
                    ->sortable(),

                TextColumn::make('unit')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('purchase_price')
                    ->label('Cost')
                    ->money('USD')
                    ->toggleable(),

                TextColumn::make('selling_price')
                    ->label('Sale')
                    ->money('USD')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship(
                        'branches',
                        'name',
                        fn (Builder $query) =>
                        Filament::auth()->user() instanceof \App\Models\User
                            ? $query->whereHas('users', fn ($u) =>
                        $u->where('users.id', Filament::auth()->id())
                        )
                            : null
                    )
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('branches', fn ($b) =>
                            $b->where('branches.id', $data['value'])
                            );
                        }
                    })
                    ->searchable()
                    ->preload(),


            ])
            ->striped()
            ->paginated([10,25, 50, 100])
            ->defaultSort('current_stock', 'asc')
            ->emptyStateHeading('No stock data')
            ->emptyStateDescription('Products with inventory tracking will appear here.');
    }

    /* ============================================================
     |  FILTER-AWARE TOP STATS (THE 3 MODULES YOU ASKED)
     ============================================================ */

    protected function filteredProductsQuery(): Builder
    {
        return $this->getFilteredTableQuery();
    }

    public function getTopStats(): array
    {
        $filteredQuery = $this->filteredProductsQuery();

        // Product IDs in scope
        $productIds = (clone $filteredQuery)->pluck('products.id');


        // Quantities
        $totalProducts = (clone $filteredQuery)->count();

        $totalPurchasedQty = DB::table('purchase_items')
            ->whereIn('product_id', $productIds)
            ->sum('quantity');

        $totalSoldQty = DB::table('sale_items')
            ->whereIn('product_id', $productIds)
            ->sum('quantity');

        // Available stock
        $availableStock = $totalPurchasedQty - $totalSoldQty;

        // Monetary totals
        $totalSellingValue = DB::table('sale_items as si')
            ->join('products as p', 'p.id', '=', 'si.product_id')
            ->whereIn('si.product_id', $productIds)
            ->sum(DB::raw('si.quantity * p.selling_price'));

        $totalBuyingCost = DB::table('purchase_items as pi')
            ->join('products as p', 'p.id', '=', 'pi.product_id')
            ->whereIn('pi.product_id', $productIds)
            ->sum(DB::raw('pi.quantity * p.purchase_price'));

        // Averages (SAFE divide)
        $avgSellingPrice = $totalSoldQty > 0
            ? $totalSellingValue / $totalSoldQty
            : 0;

        $avgBuyingPrice = $totalPurchasedQty > 0
            ? $totalBuyingCost / $totalPurchasedQty
            : 0;

        return [
            'total_products'        => (int) $totalProducts,
            'total_purchased_qty'   => (float) $totalPurchasedQty,
            'total_sold_qty'        => (float) $totalSoldQty,
            'available_stock'       => (float) $availableStock,
            'total_revenue'         => (float) $totalSellingValue,
            'avg_selling_price'     => round($avgSellingPrice, 2),
            'avg_buying_price'      => round($avgBuyingPrice, 2),
        ];
    }


}
