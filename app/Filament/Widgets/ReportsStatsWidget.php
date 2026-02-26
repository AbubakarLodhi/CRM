<?php

namespace App\Filament\Widgets;

use App\Models\Merchant;
use App\Models\Purchase;
use App\Models\Sale;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsStatsWidget extends Widget
{
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.reports-stats-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        return [
            'sales' => $this->getSalesStats(),
            'purchases' => $this->getPurchaseStats(),
            'funds' => $this->getFundStats(),
            'stock' => $this->getStockStats(),
            'returns' => $this->getReturnStats(),
            'trend' => $this->getTrendData(),
            'leaders' => $this->getLeaderboardStats(),
            'credit' => $this->getCreditStats(),
        ];
    }

    protected function authContext(): array
    {
        $user = Filament::auth()->user();

        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        return [$user, $merchantId];
    }

    protected function filters(): array
    {
        return [
            'business_id' => $this->pageFilters['business_id'] ?? null,
            'branch_id' => $this->pageFilters['branch_id'] ?? null,
            'date_from' => $this->pageFilters['date_from'] ?? null,
            'date_to' => $this->pageFilters['date_to'] ?? null,
        ];
    }

    protected function salesBaseQuery($user, string $merchantId): EloquentBuilder
    {
        $filters = $this->filters();

        $query = Sale::query()
            ->where('merchant_id', $merchantId)
            ->when(
                $filters['business_id'],
                fn (EloquentBuilder $query, $businessId) => $query->whereHas('items', fn ($q) =>
                    $q->where('sale_items.business_id', $businessId)
                ),
            )
            ->when(
                $filters['branch_id'],
                fn (EloquentBuilder $query, $branchId) => $query->whereHas('items', fn ($q) =>
                    $q->where('sale_items.branch_id', $branchId)
                ),
            )
            ->when(
                $filters['date_from'],
                fn (EloquentBuilder $query, $date) => $query->whereDate('sale_date', '>=', $date),
            )
            ->when(
                $filters['date_to'],
                fn (EloquentBuilder $query, $date) => $query->whereDate('sale_date', '<=', $date),
            );

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
    }

    protected function purchaseBaseQuery($user, string $merchantId): EloquentBuilder
    {
        $filters = $this->filters();

        $query = Purchase::query()
            ->where('merchant_id', $merchantId)
            ->when(
                $filters['business_id'],
                fn (EloquentBuilder $query, $businessId) => $query->whereHas('items', fn ($q) =>
                    $q->where('purchase_items.business_id', $businessId)
                ),
            )
            ->when(
                $filters['branch_id'],
                fn (EloquentBuilder $query, $branchId) => $query->whereHas('items', fn ($q) =>
                    $q->where('purchase_items.branch_id', $branchId)
                ),
            )
            ->when(
                $filters['date_from'],
                fn (EloquentBuilder $query, $date) => $query->whereDate('purchase_date', '>=', $date),
            )
            ->when(
                $filters['date_to'],
                fn (EloquentBuilder $query, $date) => $query->whereDate('purchase_date', '<=', $date),
            );

        if ($user instanceof \App\Models\User) {
            $query->whereHas('items.branch.users', fn ($q) =>
                $q->where('users.id', $user->id)
            );
        }

        return $query;
    }

    protected function getLeaderboardStats(): array
    {
        [$user, $merchantId] = $this->authContext();

        if (! $merchantId) {
            return [
                'customers' => [],
                'vendors' => [],
                'variants' => [],
            ];
        }

        $salesQuery = $this->salesBaseQuery($user, $merchantId);
        $purchaseQuery = $this->purchaseBaseQuery($user, $merchantId);

        $saleIds = (clone $salesQuery)->pluck('sales.id');
        $purchaseIds = (clone $purchaseQuery)->pluck('purchases.id');

        $topCustomers = $saleIds->isEmpty()
            ? collect()
            : DB::table('sales')
                ->join('customers', 'customers.id', '=', 'sales.customer_id')
                ->whereIn('sales.id', $saleIds)
                ->selectRaw('customers.id as customer_id, customers.name as customer_name')
                ->selectRaw('COUNT(sales.id) as total_sales')
                ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as total_amount')
                ->groupBy('customers.id', 'customers.name')
                ->orderByDesc('total_amount')
                ->limit(3)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->customer_id,
                    'name' => $row->customer_name ?? 'N/A',
                    'count' => (int) $row->total_sales,
                    'amount' => (float) $row->total_amount,
                ]);

        $topVendors = $purchaseIds->isEmpty()
            ? collect()
            : DB::table('purchases')
                ->join('vendors', 'vendors.id', '=', 'purchases.vendor_id')
                ->whereIn('purchases.id', $purchaseIds)
                ->selectRaw('vendors.id as vendor_id, vendors.name as vendor_name')
                ->selectRaw('COUNT(purchases.id) as total_purchases')
                ->selectRaw('COALESCE(SUM(purchases.total_amount), 0) as total_amount')
                ->groupBy('vendors.id', 'vendors.name')
                ->orderByDesc('total_amount')
                ->limit(3)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->vendor_id,
                    'name' => $row->vendor_name ?? 'N/A',
                    'count' => (int) $row->total_purchases,
                    'amount' => (float) $row->total_amount,
                ]);

        $soldVariants = $saleIds->isEmpty()
            ? collect()
            : DB::table('sale_item_variants as siv')
                ->join('sale_items as si', 'si.id', '=', 'siv.sale_item_id')
                ->join('product_variants as pv', 'pv.id', '=', 'siv.product_variant_id')
                ->leftJoin('products as p', 'p.id', '=', 'pv.product_id')
                ->whereIn('si.sale_id', $saleIds)
                ->selectRaw('pv.id as variant_id')
                ->selectRaw('COALESCE(NULLIF(pv.name, \'\'), pv.sku, \'Variant\') as variant_name')
                ->selectRaw('COALESCE(p.name, \'Product\') as product_name')
                ->selectRaw('COALESCE(pv.sku, \'-\') as sku')
                ->selectRaw('COALESCE(SUM(siv.quantity), 0) as sold_qty')
                ->selectRaw('COALESCE(SUM(siv.line_total), 0) as sold_amount')
                ->groupBy('pv.id', 'pv.name', 'pv.sku', 'p.name')
                ->get();

        $returnedQtyByVariant = $saleIds->isEmpty()
            ? collect()
            : DB::table('sale_return_item_variants as srv')
                ->join('sale_return_items as sri', 'sri.id', '=', 'srv.sale_return_item_id')
                ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                ->whereIn('sr.sale_id', $saleIds)
                ->groupBy('srv.product_variant_id')
                ->selectRaw('srv.product_variant_id as variant_id')
                ->selectRaw('COALESCE(SUM(srv.quantity), 0) as returned_qty')
                ->pluck('returned_qty', 'variant_id');

        $topVariants = $soldVariants
            ->map(function ($row) use ($returnedQtyByVariant) {
                $returnedQty = (float) ($returnedQtyByVariant[$row->variant_id] ?? 0);
                $netSoldQty = max(0, (float) $row->sold_qty - $returnedQty);

                return [
                    'id' => $row->variant_id,
                    'name' => $row->variant_name,
                    'product' => $row->product_name,
                    'sku' => $row->sku,
                    'qty' => $netSoldQty,
                    'amount' => (float) $row->sold_amount,
                ];
            })
            ->sortByDesc('qty')
            ->take(3)
            ->values();

        return [
            'customers' => $topCustomers->values()->all(),
            'vendors' => $topVendors->values()->all(),
            'variants' => $topVariants->all(),
        ];
    }

    protected function getSalesStats(): array
    {
        [$user, $merchantId] = $this->authContext();

        if (! $merchantId) {
            return $this->emptySalesStats();
        }

        $query = $this->salesBaseQuery($user, $merchantId);

        $saleIds = (clone $query)->pluck('sales.id');

        if ($saleIds->isEmpty()) {
            return $this->emptySalesStats();
        }

        $totalSales = $saleIds->count();
        $totalItemLines = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->count();

        $totalQuantitySold = DB::table('sale_item_variants as sv')
            ->join('sale_items as si', 'si.id', '=', 'sv.sale_item_id')
            ->whereIn('si.sale_id', $saleIds)
            ->sum('sv.quantity');

        $totalAmount   = (clone $query)->sum('total_amount');
        $totalDiscount = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->sum(DB::raw('line_total * (discount / 100.0)'));

        $totalTax = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->sum(DB::raw('(line_total - (line_total * (discount / 100.0))) * (tax / 100.0)'));
        $totalSubtotal = (clone $query)->sum('subtotal');

        $returnTotals = DB::table('sale_returns')
            ->whereIn('sale_id', $saleIds)
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(total_discount), 0) as total_discount')
            ->selectRaw('COALESCE(SUM(total_tax), 0) as total_tax')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->first();

        $returnedQuantity = DB::table('sale_return_item_variants as srv')
            ->join('sale_return_items as sri', 'sri.id', '=', 'srv.sale_return_item_id')
            ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
            ->whereIn('sr.sale_id', $saleIds)
            ->sum('srv.quantity');

        $netAmount = $totalAmount - (float) ($returnTotals->total_amount ?? 0);
        $netDiscount = $totalDiscount - (float) ($returnTotals->total_discount ?? 0);
        $netTax = $totalTax - (float) ($returnTotals->total_tax ?? 0);
        $netSubtotal = $totalSubtotal - (float) ($returnTotals->subtotal ?? 0);
        $netQuantity = $totalQuantitySold - (float) $returnedQuantity;

        $avgSale = $totalSales > 0 ? $netAmount / $totalSales : 0;

        return [
            'total_sales'        => (int) $totalSales,
            'total_items_count'  => (int) $totalItemLines,
            'total_quantity'     => (float) $netQuantity,
            'total_amount'       => (float) $netAmount,
            'total_discount'     => (float) $netDiscount,
            'total_tax'          => (float) $netTax,
            'total_subtotal'     => (float) $netSubtotal,
            'avg_sale'           => round($avgSale, 2),
        ];
    }

    protected function emptySalesStats(): array
    {
        return [
            'total_sales'        => 0,
            'total_items_count'  => 0,
            'total_quantity'     => 0,
            'total_amount'       => 0,
            'total_discount'     => 0,
            'total_tax'          => 0,
            'total_subtotal'     => 0,
            'avg_sale'           => 0,
        ];
    }

    protected function getPurchaseStats(): array
    {
        [$user, $merchantId] = $this->authContext();

        if (! $merchantId) {
            return $this->emptyPurchaseStats();
        }

        $query = $this->purchaseBaseQuery($user, $merchantId);

        $purchaseIds = (clone $query)->pluck('purchases.id');

        if ($purchaseIds->isEmpty()) {
            return $this->emptyPurchaseStats();
        }

        $totalPurchases = $purchaseIds->count();

        $totalItemLines = DB::table('purchase_items')
            ->whereIn('purchase_id', $purchaseIds)
            ->count();

        $totalItemQuantity = DB::table('purchase_item_variants as piv')
            ->join('purchase_items as pi', 'pi.id', '=', 'piv.purchase_item_id')
            ->whereIn('pi.purchase_id', $purchaseIds)
            ->sum('piv.quantity');

        $totalAmount   = (clone $query)->sum('total_amount');
        $totalDiscount = DB::table('purchase_items')
            ->whereIn('purchase_id', $purchaseIds)
            ->sum(DB::raw('line_total * (discount / 100.0)'));

        $totalTax = DB::table('purchase_items')
            ->whereIn('purchase_id', $purchaseIds)
            ->sum(DB::raw('(line_total - (line_total * (discount / 100.0))) * (tax / 100.0)'));
        $totalSubtotal = (clone $query)->sum('subtotal');

        $returnTotals = DB::table('purchase_returns')
            ->whereIn('purchase_id', $purchaseIds)
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(total_discount), 0) as total_discount')
            ->selectRaw('COALESCE(SUM(total_tax), 0) as total_tax')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->first();

        $returnedQuantity = DB::table('purchase_return_item_variants as prv')
            ->join('purchase_return_items as pri', 'pri.id', '=', 'prv.purchase_return_item_id')
            ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
            ->whereIn('pr.purchase_id', $purchaseIds)
            ->sum('prv.quantity');

        $netAmount = $totalAmount - (float) ($returnTotals->total_amount ?? 0);
        $netDiscount = $totalDiscount - (float) ($returnTotals->total_discount ?? 0);
        $netTax = $totalTax - (float) ($returnTotals->total_tax ?? 0);
        $netSubtotal = $totalSubtotal - (float) ($returnTotals->subtotal ?? 0);
        $netQuantity = $totalItemQuantity - (float) $returnedQuantity;

        $avgPurchase = $totalPurchases > 0 ? $netAmount / $totalPurchases : 0;

        return [
            'total_purchases'      => (int) $totalPurchases,
            'total_items_count'    => (int) $totalItemLines,
            'total_items_quantity' => (float) $netQuantity,
            'total_amount'         => (float) $netAmount,
            'total_discount'       => (float) $netDiscount,
            'total_tax'            => (float) $netTax,
            'total_subtotal'       => (float) $netSubtotal,
            'avg_purchase'         => round($avgPurchase, 2),
        ];
    }

    protected function emptyPurchaseStats(): array
    {
        return [
            'total_purchases'      => 0,
            'total_items_count'    => 0,
            'total_items_quantity' => 0,
            'total_amount'         => 0,
            'total_discount'       => 0,
            'total_tax'            => 0,
            'total_subtotal'       => 0,
            'avg_purchase'         => 0,
        ];
    }

    protected function getStockStats(): array
    {
        [$user, $merchantId] = $this->authContext();

        $variantIds = collect();

        if ($user instanceof \App\Models\User) {
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
            if ($merchantId) {
                $variantIds = DB::table('product_variants')
                    ->where('merchant_id', $merchantId)
                    ->where('is_active', true)
                    ->pluck('id');
            }
        }

        if ($variantIds->isEmpty()) {
            return [
                'total_products'      => 0,
                'total_purchased_qty' => 0,
                'total_sold_qty'      => 0,
                'available_stock'     => 0,
                'total_revenue'       => 0,
                'avg_selling_price'   => 0,
                'avg_buying_price'    => 0,
            ];
        }

        $totalProducts = $variantIds->count();

        $saleIds = $this->salesBaseQuery($user, $merchantId)->pluck('sales.id');
        $purchaseIds = $this->purchaseBaseQuery($user, $merchantId)->pluck('purchases.id');

        $totalPurchasedQty = $purchaseIds->isEmpty()
            ? 0
            : DB::table('purchase_item_variants as piv')
                ->join('purchase_items as pi', 'pi.id', '=', 'piv.purchase_item_id')
                ->whereIn('pi.purchase_id', $purchaseIds)
                ->whereIn('piv.product_variant_id', $variantIds)
                ->sum('piv.quantity');

        $totalPurchaseReturnedQty = $purchaseIds->isEmpty()
            ? 0
            : DB::table('purchase_return_item_variants as prv')
                ->join('purchase_return_items as pri', 'pri.id', '=', 'prv.purchase_return_item_id')
                ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
                ->whereIn('pr.purchase_id', $purchaseIds)
                ->whereIn('prv.product_variant_id', $variantIds)
                ->sum('prv.quantity');

        $netPurchasedQty = $totalPurchasedQty - $totalPurchaseReturnedQty;

        $totalSoldQty = $saleIds->isEmpty()
            ? 0
            : DB::table('sale_item_variants as siv')
                ->join('sale_items as si', 'si.id', '=', 'siv.sale_item_id')
                ->whereIn('si.sale_id', $saleIds)
                ->whereIn('siv.product_variant_id', $variantIds)
                ->sum('siv.quantity');

        $totalReturnedQty = $saleIds->isEmpty()
            ? 0
            : DB::table('sale_return_item_variants as srv')
                ->join('sale_return_items as sri', 'sri.id', '=', 'srv.sale_return_item_id')
                ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                ->whereIn('sr.sale_id', $saleIds)
                ->whereIn('srv.product_variant_id', $variantIds)
                ->sum('srv.quantity');

        $netSoldQty = $totalSoldQty - $totalReturnedQty;

        $availableStock = $netPurchasedQty - $netSoldQty;

        $totalRevenue = $saleIds->isEmpty()
            ? 0
            : DB::table('sale_item_variants as siv')
                ->join('sale_items as si', 'si.id', '=', 'siv.sale_item_id')
                ->join('product_variants as pv', 'pv.id', '=', 'siv.product_variant_id')
                ->whereIn('si.sale_id', $saleIds)
                ->whereIn('pv.id', $variantIds)
                ->sum(DB::raw('siv.quantity * pv.selling_price'));

        $returnedRevenue = $saleIds->isEmpty()
            ? 0
            : DB::table('sale_return_item_variants as srv')
                ->join('sale_return_items as sri', 'sri.id', '=', 'srv.sale_return_item_id')
                ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                ->join('product_variants as pv', 'pv.id', '=', 'srv.product_variant_id')
                ->whereIn('sr.sale_id', $saleIds)
                ->whereIn('pv.id', $variantIds)
                ->sum(DB::raw('srv.quantity * pv.selling_price'));

        $netRevenue = $totalRevenue - $returnedRevenue;
        $totalBuyingCost = $purchaseIds->isEmpty()
            ? 0
            : DB::table('purchase_item_variants as piv')
                ->join('purchase_items as pi', 'pi.id', '=', 'piv.purchase_item_id')
                ->join('product_variants as pv', 'pv.id', '=', 'piv.product_variant_id')
                ->whereIn('pi.purchase_id', $purchaseIds)
                ->whereIn('pv.id', $variantIds)
                ->sum(DB::raw('piv.quantity * pv.purchase_price'));

        $returnedBuyingCost = $purchaseIds->isEmpty()
            ? 0
            : DB::table('purchase_return_item_variants as prv')
                ->join('purchase_return_items as pri', 'pri.id', '=', 'prv.purchase_return_item_id')
                ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
                ->join('product_variants as pv', 'pv.id', '=', 'prv.product_variant_id')
                ->whereIn('pr.purchase_id', $purchaseIds)
                ->whereIn('pv.id', $variantIds)
                ->sum(DB::raw('prv.quantity * pv.purchase_price'));

        $netBuyingCost = $totalBuyingCost - $returnedBuyingCost;

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

    protected function getReturnStats(): array
    {
        [$user, $merchantId] = $this->authContext();

        if (! $merchantId) {
            return [
                'sales' => [
                    'total_returns' => 0,
                    'total_amount' => 0,
                    'total_quantity' => 0,
                ],
                'purchases' => [
                    'total_returns' => 0,
                    'total_amount' => 0,
                    'total_quantity' => 0,
                ],
            ];
        }

        $salesQuery = $this->salesBaseQuery($user, $merchantId);
        $purchaseQuery = $this->purchaseBaseQuery($user, $merchantId);

        $saleIds = (clone $salesQuery)->pluck('sales.id');
        $purchaseIds = (clone $purchaseQuery)->pluck('purchases.id');

        $saleReturnIds = $saleIds->isEmpty()
            ? collect()
            : DB::table('sale_returns')->whereIn('sale_id', $saleIds)->pluck('id');

        $purchaseReturnIds = $purchaseIds->isEmpty()
            ? collect()
            : DB::table('purchase_returns')->whereIn('purchase_id', $purchaseIds)->pluck('id');

        $saleReturnsTotalAmount = $saleReturnIds->isEmpty()
            ? 0
            : (float) DB::table('sale_returns')
                ->whereIn('id', $saleReturnIds)
                ->sum('total_amount');

        $saleReturnsQuantity = $saleReturnIds->isEmpty()
            ? 0
            : (float) DB::table('sale_return_item_variants as srv')
                ->join('sale_return_items as sri', 'sri.id', '=', 'srv.sale_return_item_id')
                ->whereIn('sri.sale_return_id', $saleReturnIds)
                ->sum('srv.quantity');

        $purchaseReturnsTotalAmount = $purchaseReturnIds->isEmpty()
            ? 0
            : (float) DB::table('purchase_returns')
                ->whereIn('id', $purchaseReturnIds)
                ->sum('total_amount');

        $purchaseReturnsQuantity = $purchaseReturnIds->isEmpty()
            ? 0
            : (float) DB::table('purchase_return_item_variants as prv')
                ->join('purchase_return_items as pri', 'pri.id', '=', 'prv.purchase_return_item_id')
                ->whereIn('pri.purchase_return_id', $purchaseReturnIds)
                ->sum('prv.quantity');

        return [
            'sales' => [
                'total_returns' => (int) $saleReturnIds->count(),
                'total_amount' => $saleReturnsTotalAmount,
                'total_quantity' => $saleReturnsQuantity,
            ],
            'purchases' => [
                'total_returns' => (int) $purchaseReturnIds->count(),
                'total_amount' => $purchaseReturnsTotalAmount,
                'total_quantity' => $purchaseReturnsQuantity,
            ],
        ];
    }

    protected function getTrendData(): array
    {
        [$user, $merchantId] = $this->authContext();

        $months = collect(range(5, 0))
            ->map(fn ($offset) => Carbon::now()->startOfMonth()->subMonths($offset));

        $labels = $months->map(fn (Carbon $date) => $date->format('M'))->values();
        $salesSeries = $months->map(fn () => 0)->values();
        $purchaseSeries = $months->map(fn () => 0)->values();

        if (! $merchantId) {
            return [
                'labels' => $labels->all(),
                'sales' => $salesSeries->all(),
                'purchases' => $purchaseSeries->all(),
            ];
        }

        $salesQuery = $this->salesBaseQuery($user, $merchantId);
        $purchaseQuery = $this->purchaseBaseQuery($user, $merchantId);

        $saleIds = (clone $salesQuery)->pluck('sales.id');
        $purchaseIds = (clone $purchaseQuery)->pluck('purchases.id');

        foreach ($months as $index => $month) {
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $salesSeries[$index] = $saleIds->isEmpty()
                ? 0
                : (int) DB::table('sales')
                    ->whereIn('id', $saleIds)
                    ->whereBetween('created_at', [$start, $end])
                    ->count();

            $purchaseSeries[$index] = $purchaseIds->isEmpty()
                ? 0
                : (int) DB::table('purchases')
                    ->whereIn('id', $purchaseIds)
                    ->whereBetween('created_at', [$start, $end])
                    ->count();
        }

        return [
            'labels' => $labels->all(),
            'sales' => $salesSeries->all(),
            'purchases' => $purchaseSeries->all(),
        ];
    }

    protected function getCreditStats(): array
    {
        [$user, $merchantId] = $this->authContext();

        if (! $merchantId) {
            return [
                'receivable_total' => 0,
                'payable_total' => 0,
                'top_customers' => [],
                'top_vendors' => [],
            ];
        }

        $creditSalesQuery = $this->salesBaseQuery($user, $merchantId)
            ->whereRaw('LOWER(payment_type) = ?', ['credit']);

        $creditPurchasesQuery = $this->purchaseBaseQuery($user, $merchantId)
            ->whereRaw('LOWER(payment_type) = ?', ['credit']);

        $creditSaleIds = (clone $creditSalesQuery)->pluck('sales.id');
        $creditPurchaseIds = (clone $creditPurchasesQuery)->pluck('purchases.id');

        $creditSalesTotal = (float) (clone $creditSalesQuery)->sum('total_amount');
        $creditPurchasesTotal = (float) (clone $creditPurchasesQuery)->sum('total_amount');

        $creditSalesReturns = $creditSaleIds->isEmpty()
            ? 0
            : (float) DB::table('sale_returns')
                ->whereIn('sale_id', $creditSaleIds)
                ->sum('total_amount');

        $creditPurchaseReturns = $creditPurchaseIds->isEmpty()
            ? 0
            : (float) DB::table('purchase_returns')
                ->whereIn('purchase_id', $creditPurchaseIds)
                ->sum('total_amount');

        $customerCredits = $creditSaleIds->isEmpty()
            ? collect()
            : DB::table('sales')
                ->join('customers', 'customers.id', '=', 'sales.customer_id')
                ->whereIn('sales.id', $creditSaleIds)
                ->selectRaw('customers.id as customer_id, customers.name as customer_name')
                ->selectRaw('COUNT(sales.id) as credit_sales')
                ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as credit_amount')
                ->groupBy('customers.id', 'customers.name')
                ->get();

        $customerReturns = $creditSaleIds->isEmpty()
            ? collect()
            : DB::table('sale_returns')
                ->join('sales', 'sales.id', '=', 'sale_returns.sale_id')
                ->whereIn('sale_returns.sale_id', $creditSaleIds)
                ->groupBy('sales.customer_id')
                ->selectRaw('sales.customer_id as customer_id')
                ->selectRaw('COALESCE(SUM(sale_returns.total_amount), 0) as returned_amount')
                ->pluck('returned_amount', 'customer_id');

        $topCustomers = $customerCredits
            ->map(function ($row) use ($customerReturns) {
                $returned = (float) ($customerReturns[$row->customer_id] ?? 0);
                $net = max(0, (float) $row->credit_amount - $returned);

                return [
                    'id' => $row->customer_id,
                    'name' => $row->customer_name ?? 'N/A',
                    'count' => (int) $row->credit_sales,
                    'amount' => $net,
                ];
            })
            ->sortByDesc('amount')
            ->take(2)
            ->values()
            ->all();

        $vendorCredits = $creditPurchaseIds->isEmpty()
            ? collect()
            : DB::table('purchases')
                ->join('vendors', 'vendors.id', '=', 'purchases.vendor_id')
                ->whereIn('purchases.id', $creditPurchaseIds)
                ->selectRaw('vendors.id as vendor_id, vendors.name as vendor_name')
                ->selectRaw('COUNT(purchases.id) as credit_purchases')
                ->selectRaw('COALESCE(SUM(purchases.total_amount), 0) as credit_amount')
                ->groupBy('vendors.id', 'vendors.name')
                ->get();

        $vendorReturns = $creditPurchaseIds->isEmpty()
            ? collect()
            : DB::table('purchase_returns')
                ->join('purchases', 'purchases.id', '=', 'purchase_returns.purchase_id')
                ->whereIn('purchase_returns.purchase_id', $creditPurchaseIds)
                ->groupBy('purchases.vendor_id')
                ->selectRaw('purchases.vendor_id as vendor_id')
                ->selectRaw('COALESCE(SUM(purchase_returns.total_amount), 0) as returned_amount')
                ->pluck('returned_amount', 'vendor_id');

        $topVendors = $vendorCredits
            ->map(function ($row) use ($vendorReturns) {
                $returned = (float) ($vendorReturns[$row->vendor_id] ?? 0);
                $net = max(0, (float) $row->credit_amount - $returned);

                return [
                    'id' => $row->vendor_id,
                    'name' => $row->vendor_name ?? 'N/A',
                    'count' => (int) $row->credit_purchases,
                    'amount' => $net,
                ];
            })
            ->sortByDesc('amount')
            ->take(2)
            ->values()
            ->all();

        return [
            'receivable_total' => max(0, $creditSalesTotal - $creditSalesReturns),
            'payable_total' => max(0, $creditPurchasesTotal - $creditPurchaseReturns),
            'top_customers' => $topCustomers,
            'top_vendors' => $topVendors,
        ];
    }

    protected function getFundStats(): array
    {
        [$user, $merchantId] = $this->authContext();

        if (! $merchantId) {
            return [
                'opening_total_funds' => 0,
                'sales_cash_inflow' => 0,
                'purchases_cash_outflow' => 0,
                'net_cash_movement' => 0,
                'current_total_funds' => 0,
            ];
        }

        $merchant = Merchant::query()->find($merchantId);

        $openingTotalFunds = (float) ($merchant?->cash_in_hand ?? 0) + (float) ($merchant?->cash_in_bank ?? 0);

        $cashSalesQuery = $this->salesBaseQuery($user, $merchantId)
            ->whereRaw('LOWER(payment_type) = ?', ['cash']);

        $cashPurchasesQuery = $this->purchaseBaseQuery($user, $merchantId)
            ->whereRaw('LOWER(payment_type) = ?', ['cash']);

        $cashSaleIds = (clone $cashSalesQuery)->pluck('sales.id');
        $cashPurchaseIds = (clone $cashPurchasesQuery)->pluck('purchases.id');

        $cashSalesAmount = (float) (clone $cashSalesQuery)->sum('total_amount');
        $cashPurchasesAmount = (float) (clone $cashPurchasesQuery)->sum('total_amount');

        $cashSaleReturns = $cashSaleIds->isEmpty()
            ? 0
            : (float) DB::table('sale_returns')
                ->whereIn('sale_id', $cashSaleIds)
                ->sum('total_amount');

        $cashPurchaseReturns = $cashPurchaseIds->isEmpty()
            ? 0
            : (float) DB::table('purchase_returns')
                ->whereIn('purchase_id', $cashPurchaseIds)
                ->sum('total_amount');

        $salesCashInflow = $cashSalesAmount - $cashSaleReturns;
        $purchasesCashOutflow = $cashPurchasesAmount - $cashPurchaseReturns;
        $netCashMovement = $salesCashInflow - $purchasesCashOutflow;

        return [
            'opening_total_funds' => $openingTotalFunds,
            'sales_cash_inflow' => $salesCashInflow,
            'purchases_cash_outflow' => $purchasesCashOutflow,
            'net_cash_movement' => $netCashMovement,
            'current_total_funds' => $openingTotalFunds + $netCashMovement,
        ];
    }
}
