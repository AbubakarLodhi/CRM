<?php

namespace App\Filament\Resources\Vendors\Tables;

use App\Filament\Exports\VendorPurchasesExport;
use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Vendor;
use App\Models\Purchase;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('phone')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email address')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('occupation')
                    ->label('Occupation')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('merchant.name')
                    ->label('Merchant')
                    ->color('primary')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('reference')
                    ->label('Reference Vendor')
                    ->limit(30)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordUrl(fn (Vendor $record) =>
                auth(Filament::getCurrentPanel()->getAuthGuard())
                    ->user()
                    ?->hasPermissionTo('vendors.update', Filament::getCurrentPanel()->getAuthGuard())
                    ? VendorResource::getUrl('edit', [
                        'record' => $record,
                    ])
                    : null
            )
            ->recordActions([
                Action::make('export_vendor_purchases')
                    ->label('')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->tooltip('Export Vendor Purchases')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('reports.view', Filament::getCurrentPanel()->getAuthGuard()))
                    ->action(function (Vendor $record) {
                        $user = Filament::auth()->user();

                        $merchantId = match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                               => null,
                        };

                        $baseQuery = Purchase::query()
                            ->where('vendor_id', $record->id)
                            ->when($merchantId, fn ($q) =>
                                $q->where('merchant_id', $merchantId)
                            );

                        if ($user instanceof \App\Models\User) {
                            $baseQuery->whereHas('items.branch.users', fn ($q) =>
                                $q->where('users.id', $user->id)
                            );
                        }

                        $exportQuery = (clone $baseQuery)
                            ->withCount('items')
                            ->with([
                                'merchant',
                                'vendor',
                                'items.branch',
                            ]);

                        $purchaseIds = (clone $baseQuery)->select('purchases.id');

                        $totals = [
                            'items_count' => (int) DB::table('purchase_items')
                                ->whereIn('purchase_id', $purchaseIds)
                                ->count(),

                            'quantity' => (float) DB::table('purchase_item_variants as piv')
                                ->join('purchase_items as pi', 'pi.id', '=', 'piv.purchase_item_id')
                                ->whereIn('pi.purchase_id', $purchaseIds)
                                ->sum('piv.quantity'),

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

                        $totals['quantity'] = (float) $totals['quantity'] - (float) $returnedQuantity;
                        $totals['subtotal'] = (float) $totals['subtotal'] - (float) ($returnTotals->subtotal ?? 0);
                        $totals['discount'] = (float) $totals['discount'] - (float) ($returnTotals->total_discount ?? 0);
                        $totals['tax'] = (float) $totals['tax'] - (float) ($returnTotals->total_tax ?? 0);
                        $totals['total'] = (float) $totals['total'] - (float) ($returnTotals->total_amount ?? 0);

                        $cashQuery = (clone $baseQuery)->whereRaw('LOWER(payment_type) = ?', ['cash']);
                        $creditQuery = (clone $baseQuery)->whereRaw('LOWER(payment_type) = ?', ['credit']);

                        $cashPurchaseIds = (clone $cashQuery)->select('purchases.id');
                        $creditPurchaseIds = (clone $creditQuery)->select('purchases.id');

                        $cashTotal = (float) (clone $cashQuery)->sum('total_amount');
                        $creditTotal = (float) (clone $creditQuery)->sum('total_amount');

                        $cashReturns = (float) DB::table('purchase_returns')
                            ->whereIn('purchase_id', $cashPurchaseIds)
                            ->sum('total_amount');

                        $creditReturns = (float) DB::table('purchase_returns')
                            ->whereIn('purchase_id', $creditPurchaseIds)
                            ->sum('total_amount');

                        $totals['total_amount'] = (float) $totals['total'];
                        $totals['amount_paid'] = $cashTotal - $cashReturns;
                        $totals['amount_pending'] = $creditTotal - $creditReturns;

                        $filename = 'vendor-purchases-' . ($record->name ?? 'vendor') . '-' . now()->format('Y-m-d_H-i-s') . '.xlsx';

                        return Excel::download(
                            new VendorPurchasesExport($exportQuery, $totals),
                            $filename
                        );
                    }),

                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('vendors.update', Filament::getCurrentPanel()->getAuthGuard())),

                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('vendors.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('vendors.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
