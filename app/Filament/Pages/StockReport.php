<?php

namespace App\Filament\Pages;

use App\Models\ProductVariant;
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
                    WHERE piv.product_variant_id = product_variants.id
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
                    WHERE siv.product_variant_id = product_variants.id
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
                                     WHERE piv.product_variant_id = product_variants.id),
                                0) as total_purchased'
                    )
                    ->selectRaw(
                        $user instanceof \App\Models\User
                            ? $this->soldExpression($user->id) . ' as total_sold'
                            : '
                                COALESCE(
                                    (SELECT SUM(siv.quantity)
                                     FROM sale_item_variants siv
                                     WHERE siv.product_variant_id = product_variants.id),
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
                                         WHERE piv.product_variant_id = product_variants.id),
                                    0)
                                    -
                                    COALESCE(
                                        (SELECT SUM(siv.quantity)
                                         FROM sale_item_variants siv
                                         WHERE siv.product_variant_id = product_variants.id),
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

                IconColumn::make('is_active')->boolean()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultSort('current_stock', 'asc');
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
                ->whereIn('si.branch_id', $branchIds)
                ->pluck('sv.product_variant_id');

            $purchasedVariantIds = DB::table('purchase_item_variants as pv')
                ->join('purchase_items as pi', 'pi.id', '=', 'pv.purchase_item_id')
                ->whereIn('pi.branch_id', $branchIds)
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
            ->whereIn('piv.product_variant_id', $variantIds)
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereIn('pi.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum('piv.quantity');


        /* SOLD */
        $totalSoldQty = DB::table('sale_item_variants as siv')
            ->join('sale_items as si', 'si.id', '=', 'siv.sale_item_id')
            ->whereIn('siv.product_variant_id', $variantIds)
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereIn('si.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum('siv.quantity');

        $availableStock = $totalPurchasedQty - $totalSoldQty;

        /* REVENUE */
        $totalRevenue = DB::table('sale_item_variants as siv')
            ->join('sale_items as si', 'si.id', '=', 'siv.sale_item_id')
            ->join('product_variants as pv', 'pv.id', '=', 'siv.product_variant_id')
            ->whereIn('pv.id', $variantIds)
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereIn('si.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum(DB::raw('siv.quantity * pv.selling_price'));


        /* BUYING COST */
        $totalBuyingCost = DB::table('purchase_item_variants as piv')
            ->join('purchase_items as pi', 'pi.id', '=', 'piv.purchase_item_id')
            ->join('product_variants as pv', 'pv.id', '=', 'piv.product_variant_id')
            ->whereIn('pv.id', $variantIds)
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereIn('pi.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum(DB::raw('piv.quantity * pv.purchase_price'));

        return [
            'total_products'      => (int) $totalProducts,
            'total_purchased_qty' => (float) $totalPurchasedQty,
            'total_sold_qty'      => (float) $totalSoldQty,
            'available_stock'     => (float) $availableStock,
            'total_revenue'       => (float) $totalRevenue,
            'avg_selling_price'   => $totalSoldQty > 0 ? round($totalRevenue / $totalSoldQty, 2) : 0,
            'avg_buying_price'    => $totalPurchasedQty > 0 ? round($totalBuyingCost / $totalPurchasedQty, 2) : 0,
        ];
    }
}
