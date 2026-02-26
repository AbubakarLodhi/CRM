<?php

namespace App\Filament\Pages;

use App\Filament\Exports\PurchasesSummaryExport;
use App\Models\Merchant;
use App\Models\PermissionModule;
use App\Models\Purchase;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PurchasesSummary extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;
    protected static string|\UnitEnum|null $navigationGroup = 'Reportings';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Purchases Summary';
    protected static ?string $navigationLabel = 'Purchases Summary';

    protected string $view = 'filament.pages.purchases-summary';

    public static function canViewAny(): bool
    {
        $user  = Filament::auth()->user();
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        if (! $user) {
            return false;
        }

        if (! PermissionModule::isEnabledForCurrentMerchant('purchases')) {
            return false;
        }

        return $user->hasPermissionTo('purchases.view', $guard);
    }

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

                $query = Purchase::query()
                    ->where('merchant_id', $merchantId)
                    ->with([
                        'merchant',
                        'items.branch',
                    ]);

                if ($user instanceof \App\Models\User) {
                    $query->whereHas('items.branch.users', fn ($q) =>
                    $q->where('users.id', $user->id)
                    );
                }

                return $query;
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

                        // ✅ Normalize state
                        if (empty($state)) {
                            return ['-'];
                        }

                        if (is_string($state)) {
                            return $state;
                        }

                        if (! is_array($state)) {
                            return '-';
                        }

                        // ✅ Limit badges
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

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('PKR')
                    ->sortable(),

                TextColumn::make('discount')
                    ->label('Discount')
                    ->money('PKR')
                    ->getStateUsing(function (Purchase $record) {
                        return $record->items->sum(function ($item) {
                            $lineTotal = (float) ($item->line_total ?? 0);
                            $discountRate = (float) ($item->discount ?? 0);
                            return $lineTotal * ($discountRate / 100);
                        });
                    })
                    ->sortable(),

                TextColumn::make('tax')
                    ->label('Tax')
                    ->money('PKR')
                    ->getStateUsing(function (Purchase $record) {
                        return $record->items->sum(function ($item) {
                            $lineTotal = (float) ($item->line_total ?? 0);
                            $discountRate = (float) ($item->discount ?? 0);
                            $taxRate = (float) ($item->tax ?? 0);
                            $discountAmount = $lineTotal * ($discountRate / 100);
                            $taxableAmount = $lineTotal - $discountAmount;
                            return $taxableAmount * ($taxRate / 100);
                        });
                    })
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('PKR')
                    ->sortable()
                    ->weight('bold'),
            ])

            ->filters([
                Filter::make('purchase_date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('from')
                            ->label('From'),
                        DatePicker::make('to')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date) => $query->whereDate('purchase_date', '>=', $date)
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $query, $date) => $query->whereDate('purchase_date', '<=', $date)
                            );
                    }),
                /* ✅ FIXED BRANCH FILTER */
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
            ->defaultSort('purchase_date', 'desc');
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
     |  STATS (UNCHANGED)
     ============================================================ */

    public function getPurchaseStats(): array
    {
        $filteredQuery = $this->getFilteredTableQueryWithoutPagination();
        $user = Filament::auth()->user();
        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };
        $purchaseIds   = (clone $filteredQuery)->pluck('purchases.id');

        $totalPurchases = $purchaseIds->count();

        $totalItemLines = DB::table('purchase_items')
            ->whereIn('purchase_id', $purchaseIds)
            ->count();

        $totalItemQuantity = DB::table('purchase_item_variants as piv')
            ->join('purchase_items as pi', 'pi.id', '=', 'piv.purchase_item_id')
            ->whereIn('pi.purchase_id', $purchaseIds)
            ->sum('piv.quantity');

        $totalAmount   = (clone $filteredQuery)->sum('total_amount');
        $totalDiscount = DB::table('purchase_items')
            ->whereIn('purchase_id', $purchaseIds)
            ->sum(DB::raw('line_total * (discount / 100.0)'));

        $totalTax = DB::table('purchase_items')
            ->whereIn('purchase_id', $purchaseIds)
            ->sum(DB::raw('(line_total - (line_total * (discount / 100.0))) * (tax / 100.0)'));
        $totalSubtotal = (clone $filteredQuery)->sum('subtotal');

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

        $openingTotalFunds = 0.0;

        if ($merchantId) {
            $merchant = Merchant::query()->find($merchantId);
            $openingTotalFunds = (float) ($merchant?->cash_in_hand ?? 0) + (float) ($merchant?->cash_in_bank ?? 0);
        }

        $cashPurchasesQuery = (clone $filteredQuery)->whereRaw('LOWER(payment_type) = ?', ['cash']);
        $cashPurchaseIds = (clone $cashPurchasesQuery)->pluck('purchases.id');
        $cashPurchasesAmount = (float) (clone $cashPurchasesQuery)->sum('total_amount');
        $cashPurchaseReturns = $cashPurchaseIds->isEmpty()
            ? 0
            : (float) DB::table('purchase_returns')
                ->whereIn('purchase_id', $cashPurchaseIds)
                ->sum('total_amount');

        $purchasesCashEffect = $cashPurchasesAmount - $cashPurchaseReturns;
        $currentTotalFunds = $openingTotalFunds - $purchasesCashEffect;

        return [
            'total_purchases'      => (int) $totalPurchases,
            'total_items_count'    => (int) $totalItemLines,
            'total_items_quantity' => (float) $netQuantity,
            'total_amount'         => (float) $netAmount,
            'total_discount'       => (float) $netDiscount,
            'total_tax'            => (float) $netTax,
            'total_subtotal'       => (float) $netSubtotal,
            'avg_purchase'         => round($avgPurchase, 2),
            'opening_total_funds' => (float) $openingTotalFunds,
            'purchases_cash_effect' => (float) $purchasesCashEffect,
            'current_total_funds' => (float) $currentTotalFunds,
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
                            'items.branch',   // ✅ branch comes via purchase_items
                        ]);

                    // --- Totals (same filtered dataset) ---
                    $purchaseIds = (clone $baseQuery)->select('purchases.id');

                    $totals = [
                        // Items Count = number of purchase_items rows (same as withCount('items') sum)
                        'items_count' => (int) DB::table('purchase_items')
                            ->whereIn('purchase_id', $purchaseIds)
                            ->count(),

                        'subtotal' => (float) (clone $baseQuery)->sum('subtotal'),
                        'discount' => (float) DB::table('purchase_items')
                            ->whereIn('purchase_id', $purchaseIds)
                            ->sum(DB::raw('line_total * (discount / 100.0)')),

                        'tax' => (float) DB::table('purchase_items')
                            ->whereIn('purchase_id', $purchaseIds)
                            ->sum(DB::raw('(line_total - (line_total * (discount / 100.0))) * (tax / 100.0)')),
                        'total'    => (float) (clone $baseQuery)->sum('total_amount'),
                    ];

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

                    $totals['items_count'] = (int) $totals['items_count'];
                    $totals['subtotal'] = (float) $totals['subtotal'] - (float) ($returnTotals->subtotal ?? 0);
                    $totals['discount'] = (float) $totals['discount'] - (float) ($returnTotals->total_discount ?? 0);
                    $totals['tax'] = (float) $totals['tax'] - (float) ($returnTotals->total_tax ?? 0);
                    $totals['total'] = (float) $totals['total'] - (float) ($returnTotals->total_amount ?? 0);
                    $totals['quantity'] = (float) ($totals['quantity'] ?? 0) - (float) $returnedQuantity;

                    return Excel::download(
                        new PurchasesSummaryExport($exportQuery, $totals),
                        'purchases-summary-' . now()->format('Y-m-d_H-i-s') . '.xlsx'
                    );
                }),
        ];
    }

}
