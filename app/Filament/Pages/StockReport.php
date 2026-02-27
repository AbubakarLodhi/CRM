<?php

namespace App\Filament\Pages;

use App\Filament\Exports\StockReportExport;
use App\Models\Product;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Actions\Action;
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
use Maatwebsite\Excel\Facades\Excel;

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
     |  EXPRESSIONS — USED ONLY FOR TABLE ROWS
     ============================================================ */

    protected function purchasedExpression(string $userId): string
    {
        return "
            COALESCE(
                (
                    SELECT SUM(piv.quantity)
                    FROM purchase_item_variants piv
                    JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                    JOIN purchases p ON p.id = pi.purchase_id
                    WHERE piv.product_variant_id = product_variants.id
                      AND p.deleted_at IS NULL
                      AND pi.branch_id IN (
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
                    SELECT SUM(siv.quantity)
                    FROM sale_item_variants siv
                    JOIN sale_items si ON si.id = siv.sale_item_id
                    JOIN sales s ON s.id = si.sale_id
                    WHERE siv.product_variant_id = product_variants.id
                      AND s.deleted_at IS NULL
                      AND si.branch_id IN (
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
        return '(' . $this->purchasedExpression($userId) . ' - ' . $this->soldExpression($userId) . ')';
    }

    /* ============================================================
     |  TABLE (CORRECT AS-IS)
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
                ProductVariant::query()
                    ->where('product_variants.is_active', true)
                    ->when($merchantId, fn ($q) =>
                    $q->where('product_variants.merchant_id', $merchantId)
                    )
                    ->when($user instanceof \App\Models\User, fn ($q) =>
                    $q->whereHas('product.branches.users', fn ($u) =>
                    $u->where('users.id', $user->id)
                    )
                    )
                    ->with('product')
                    ->select('product_variants.*')
                    ->selectRaw(
                        $user instanceof \App\Models\User
                            ? $this->purchasedExpression($user->id) . ' as total_purchased'
                            : '
                                COALESCE(
                                    (SELECT SUM(piv.quantity)
                                     FROM purchase_item_variants piv
                                     JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                                     JOIN purchases p ON p.id = pi.purchase_id
                                     WHERE piv.product_variant_id = product_variants.id
                                       AND p.deleted_at IS NULL),
                                0) as total_purchased'
                    )
                    ->selectRaw(
                        $user instanceof \App\Models\User
                            ? $this->soldExpression($user->id) . ' as total_sold'
                            : '
                                COALESCE(
                                    (SELECT SUM(siv.quantity)
                                     FROM sale_item_variants siv
                                     JOIN sale_items si ON si.id = siv.sale_item_id
                                     JOIN sales s ON s.id = si.sale_id
                                     WHERE siv.product_variant_id = product_variants.id
                                       AND s.deleted_at IS NULL),
                                0) as total_sold'
                    )
                    ->selectRaw(
                        $user instanceof \App\Models\User
                            ? $this->stockExpression($user->id) . ' as current_stock'
                            : '
                                (
                                    COALESCE(
                                        (SELECT SUM(piv.quantity)
                                         FROM purchase_item_variants piv
                                         JOIN purchase_items pi ON pi.id = piv.purchase_item_id
                                         JOIN purchases p ON p.id = pi.purchase_id
                                         WHERE piv.product_variant_id = product_variants.id
                                           AND p.deleted_at IS NULL),
                                    0)
                                    -
                                    COALESCE(
                                        (SELECT SUM(siv.quantity)
                                         FROM sale_item_variants siv
                                         JOIN sale_items si ON si.id = siv.sale_item_id
                                         JOIN sales s ON s.id = si.sale_id
                                         WHERE siv.product_variant_id = product_variants.id
                                           AND s.deleted_at IS NULL),
                                    0)
                                ) as current_stock'
                    )
            )
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Variant')
                    ->description(fn ($record) => $record->sku)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_purchased')->label('Purchased')->numeric()->sortable(),
                TextColumn::make('total_sold')->label('Sold')->numeric()->sortable(),

                TextColumn::make('current_stock')
                    ->label('Stock')
                    ->badge()
                    ->icon(fn ($state) =>
                    $state <= 0 ? 'heroicon-o-x-circle'
                        : ($state <= 10 ? 'heroicon-o-exclamation-triangle'
                        : 'heroicon-o-check-circle')
                    )
                    ->color(fn ($state) =>
                    $state <= 0 ? 'danger'
                        : ($state <= 10 ? 'warning' : 'success')
                    )
                    ->sortable(),

                TextColumn::make('purchase_price')->label('Cost')->money('PKR')->toggleable(),
                TextColumn::make('selling_price')->label('Sale')->money('PKR')->toggleable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->color(fn ($state) => $state ? 'primary' : 'danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(function () use ($user, $merchantId) {
                        if (! $merchantId) {
                            return [];
                        }

                        $query = Product::query()
                            ->where('merchant_id', $merchantId);

                        if ($user instanceof \App\Models\User) {
                            $query->whereHas('branches.users', fn ($q) =>
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
                            ? $query->where('product_variants.product_id', $data['value'])
                            : null
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('current_stock', 'asc');
    }

    protected function getFilteredTableQueryWithoutPagination(): Builder
    {
        $query = clone $this->getFilteredTableQuery();
        $query->getQuery()->limit = null;
        $query->getQuery()->offset = null;
        return $query;
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
                        ->with('product');

                    $totalsRow = DB::query()
                        ->fromSub($baseQuery, 'stock')
                        ->selectRaw('COALESCE(sum(total_purchased), 0) as purchased')
                        ->selectRaw('COALESCE(sum(total_sold), 0) as sold')
                        ->selectRaw('COALESCE(sum(current_stock), 0) as stock')
                        ->first();

                    $totals = [
                        'purchased' => (float) ($totalsRow->purchased ?? 0),
                        'sold'      => (float) ($totalsRow->sold ?? 0),
                        'stock'     => (float) ($totalsRow->stock ?? 0),
                    ];

                    $stats = $this->getTopStats();

                    return Excel::download(
                        new StockReportExport($exportQuery, $totals, $stats),
                        'stock-report-' . now()->format('Y-m-d_H-i-s') . '.xlsx'
                    );
                }),
        ];
    }

    /* ============================================================
     |  TOP STATS — FIXED & PORTAL-SAFE
     ============================================================ */

    public function getTopStats(): array
    {
        $user = Filament::auth()->user();

        $variantIds = collect();

        if ($user instanceof \App\Models\User) {
            // STAFF → only variants used in their branches
            $branchIds = $user->branches()->pluck('branches.id');

            $soldVariantIds = DB::table('sale_item_variants as sv')
                ->join('sale_items as si', 'si.id', '=', 'sv.sale_item_id')
                ->join('sales as s', 's.id', '=', 'si.sale_id')
                ->whereIn('si.branch_id', $branchIds)
                ->whereNull('s.deleted_at')
                ->pluck('sv.product_variant_id');

            $purchasedVariantIds = DB::table('purchase_item_variants as pv')
                ->join('purchase_items as pi', 'pi.id', '=', 'pv.purchase_item_id')
                ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
                ->whereIn('pi.branch_id', $branchIds)
                ->whereNull('p.deleted_at')
                ->pluck('pv.product_variant_id');

            $variantIds = $soldVariantIds
                ->merge($purchasedVariantIds)
                ->unique()
                ->values();
        } else {
            // MERCHANT → all filtered variants
            $variantIds = $this->getFilteredTableQuery()
                ->pluck('product_variants.id');
        }



        $totalProducts = $variantIds->count();

        /* PURCHASED */
        $totalPurchasedQty = DB::table('purchase_item_variants as piv')
            ->join('purchase_items as pi', 'pi.id', '=', 'piv.purchase_item_id')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->whereIn('piv.product_variant_id', $variantIds)
            ->whereNull('p.deleted_at')
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereIn('pi.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum('piv.quantity');

        $netPurchasedQty = $totalPurchasedQty;


        /* SOLD */
        $totalSoldQty = DB::table('sale_item_variants as siv')
            ->join('sale_items as si', 'si.id', '=', 'siv.sale_item_id')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->whereIn('siv.product_variant_id', $variantIds)
            ->whereNull('s.deleted_at')
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereIn('si.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum('siv.quantity');

        $netSoldQty = $totalSoldQty;

        $availableStock = $netPurchasedQty - $netSoldQty;

        /* REVENUE */
        $totalRevenue = DB::table('sale_item_variants as siv')
            ->join('sale_items as si', 'si.id', '=', 'siv.sale_item_id')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->whereIn('siv.product_variant_id', $variantIds)
            ->whereNull('s.deleted_at')
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereIn('si.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum('siv.line_total');

        $netRevenue = $totalRevenue;

        /* BUYING COST */
        $totalBuyingCost = DB::table('purchase_item_variants as piv')
            ->join('purchase_items as pi', 'pi.id', '=', 'piv.purchase_item_id')
            ->join('product_variants as pv', 'pv.id', '=', 'piv.product_variant_id')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->whereIn('pv.id', $variantIds)
            ->whereNull('p.deleted_at')
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereIn('pi.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum(DB::raw('piv.quantity * pv.purchase_price'));

        $netBuyingCost = $totalBuyingCost;

        return [
            'total_products'      => (int) $totalProducts,
            'total_purchased_qty' => (float) $netPurchasedQty,
            'total_sold_qty'      => (float) $netSoldQty,
            'available_stock'     => (float) $availableStock,
            'total_revenue'       => (float) $netRevenue,
            'avg_selling_price'   => $netSoldQty > 0 ? round($netRevenue / $netSoldQty, 2) : 0,
            'avg_buying_price'    => $netPurchasedQty > 0 ? round($netBuyingCost / $netPurchasedQty, 2) : 0,
        ];
    }
}
