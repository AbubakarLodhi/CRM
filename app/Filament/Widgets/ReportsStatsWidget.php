<?php

namespace App\Filament\Widgets;

use App\Models\Purchase;
use App\Models\Sale;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ReportsStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.reports-stats-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        return [
            'sales' => $this->getSalesStats(),
            'purchases' => $this->getPurchaseStats(),
            'stock' => $this->getStockStats(),
        ];
    }

    protected function getSalesStats(): array
    {
        $user = Filament::auth()->user();

        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        if (! $merchantId) {
            return $this->emptySalesStats();
        }

        $query = Sale::query()
            ->where('merchant_id', $merchantId);

        if ($user instanceof \App\Models\User) {
            $query
                ->whereHas('items.business.users', fn ($q) =>
                    $q->where('users.id', $user->id)
                )
                ->whereHas('items.branch.users', fn ($q) =>
                    $q->where('users.id', $user->id)
                );
        }

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
        $user = Filament::auth()->user();

        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        if (! $merchantId) {
            return $this->emptyPurchaseStats();
        }

        $query = Purchase::query()
            ->where('merchant_id', $merchantId);

        if ($user instanceof \App\Models\User) {
            $query->whereHas('items.branch.users', fn ($q) =>
                $q->where('users.id', $user->id)
            );
        }

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

        $avgPurchase = $totalPurchases > 0 ? $totalAmount / $totalPurchases : 0;

        return [
            'total_purchases'      => (int) $totalPurchases,
            'total_items_count'    => (int) $totalItemLines,
            'total_items_quantity' => (float) $totalItemQuantity,
            'total_amount'         => (float) $totalAmount,
            'total_discount'       => (float) $totalDiscount,
            'total_tax'            => (float) $totalTax,
            'total_subtotal'       => (float) $totalSubtotal,
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
        $user = Filament::auth()->user();

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
            $merchantId = match (true) {
                $user instanceof \App\Models\Merchant => $user->id,
                $user instanceof \App\Models\User     => $user->merchant_id,
                default                               => null,
            };

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

        $totalPurchasedQty = DB::table('purchase_item_variants as piv')
            ->join('purchase_items as pi', 'pi.id', '=', 'piv.purchase_item_id')
            ->whereIn('piv.product_variant_id', $variantIds)
            ->when($user instanceof \App\Models\User, fn ($q) =>
                $q->whereIn('pi.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum('piv.quantity');

        $totalSoldQty = DB::table('sale_item_variants as siv')
            ->join('sale_items as si', 'si.id', '=', 'siv.sale_item_id')
            ->whereIn('siv.product_variant_id', $variantIds)
            ->when($user instanceof \App\Models\User, fn ($q) =>
                $q->whereIn('si.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum('siv.quantity');

        $availableStock = $totalPurchasedQty - $totalSoldQty;

        $totalRevenue = DB::table('sale_item_variants as siv')
            ->join('sale_items as si', 'si.id', '=', 'siv.sale_item_id')
            ->join('product_variants as pv', 'pv.id', '=', 'siv.product_variant_id')
            ->whereIn('pv.id', $variantIds)
            ->when($user instanceof \App\Models\User, fn ($q) =>
                $q->whereIn('si.branch_id', $user->branches()->pluck('branches.id'))
            )
            ->sum(DB::raw('siv.quantity * pv.selling_price'));

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
