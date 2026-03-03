<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Exports\CustomerSalesExport;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CustomersTable
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

                // Show merchant name instead of ID
                BadgeColumn::make('merchant.name')
                    ->label('Merchant')
                    ->color('primary')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                // Show reference customer name instead of ID
                TextColumn::make('reference')
                    ->label('Reference Customer')
                    ->limit(30)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([

            ])
            ->recordUrl(fn (Customer $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('customers.update', Filament::getCurrentPanel()->getAuthGuard())
                ? CustomerResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )

            ->recordActions([
                Action::make('export_customer_sales')
                    ->label('')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->tooltip('Export Customer Sales')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('reports.view', Filament::getCurrentPanel()->getAuthGuard()))
                    ->action(function (Customer $record) {
                        $user = Filament::auth()->user();

                        $merchantId = match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                               => null,
                        };

                        $baseQuery = Sale::query()
                            ->withoutTrashed()
                            ->where('customer_id', $record->id)
                            ->when($merchantId, fn ($q) =>
                                $q->where('merchant_id', $merchantId)
                            );

                        if ($user instanceof \App\Models\User) {
                            $baseQuery
                                ->whereHas('items.business.users', fn ($q) =>
                                    $q->where('users.id', $user->id)
                                )
                                ->whereHas('items.branch.users', fn ($q) =>
                                    $q->where('users.id', $user->id)
                                );
                        }

                        $exportQuery = (clone $baseQuery)
                            ->withCount('items')
                            ->with([
                                'merchant',
                                'customer',
                                'items.branch',
                            ]);

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
                            'discount' => (float) DB::table('sale_items')
                                ->whereIn('sale_id', $saleIds)
                                ->sum(DB::raw('line_total * (discount / 100.0)')),

                            'tax' => (float) DB::table('sale_items')
                                ->whereIn('sale_id', $saleIds)
                                ->sum(DB::raw('(line_total - (line_total * (discount / 100.0))) * (tax / 100.0)')),
                            'total'    => (float) (clone $baseQuery)->sum('total_amount'),
                        ];

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

                        $totals['quantity'] = (float) $totals['quantity'] - (float) $returnedQuantity;
                        $totals['subtotal'] = (float) $totals['subtotal'] - (float) ($returnTotals->subtotal ?? 0);
                        $totals['discount'] = (float) $totals['discount'] - (float) ($returnTotals->total_discount ?? 0);
                        $totals['tax'] = (float) $totals['tax'] - (float) ($returnTotals->total_tax ?? 0);
                        $totals['total'] = (float) $totals['total'] - (float) ($returnTotals->total_amount ?? 0);

                        $cashQuery = (clone $baseQuery)->whereRaw('LOWER(payment_type) = ?', ['cash']);
                        $creditQuery = (clone $baseQuery)->whereRaw('LOWER(payment_type) = ?', ['credit']);

                        $cashSaleIds = (clone $cashQuery)->select('sales.id');
                        $creditSaleIds = (clone $creditQuery)->select('sales.id');

                        $cashTotal = (float) (clone $cashQuery)->sum('total_amount');
                        $creditTotal = (float) (clone $creditQuery)->sum('total_amount');

                        $cashReturns = (float) DB::table('sale_returns')
                            ->whereIn('sale_id', $cashSaleIds)
                            ->sum('total_amount');

                        $creditReturns = (float) DB::table('sale_returns')
                            ->whereIn('sale_id', $creditSaleIds)
                            ->sum('total_amount');

                        $totals['total_amount'] = (float) $totals['total'];
                        $totals['amount_paid'] = $cashTotal - $cashReturns;
                        $totals['amount_pending'] = $creditTotal - $creditReturns;

                        $filename = 'customer-sales-' . ($record->name ?? 'customer') . '-' . now()->format('Y-m-d_H-i-s') . '.xlsx';

                        return Excel::download(
                            new CustomerSalesExport($exportQuery, $totals),
                            $filename
                        );
                    }),
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('customers.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('customers.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('customers.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
