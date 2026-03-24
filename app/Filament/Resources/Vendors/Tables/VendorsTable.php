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
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

                TextColumn::make('total_amount')
                    ->label('Total Amount (PKR)')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->getStateUsing(function (Vendor $record) {
                        return Purchase::where('vendor_id', $record->id)
                            ->sum('total_amount');
                    })
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Amount Paid (PKR)')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->getStateUsing(function (Vendor $record) {
                        return (float) Purchase::where('vendor_id', $record->id)
                            ->sum('paid_amount');
                    }),

                TextColumn::make('amount_pending')
                    ->label('Amount Pending (PKR)')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->getStateUsing(function (Vendor $record) {
                        return (float) Purchase::where('vendor_id', $record->id)
                            ->sum('due_amount');
                    }),
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
                    ?->hasPermissionTo('vendors.view', Filament::getCurrentPanel()->getAuthGuard())
                    ? VendorResource::getUrl('purchases', [
                        'record' => $record,
                    ])
                    : null
            )
            ->recordActions([
                Action::make('export_vendor_purchases')
                    ->label('')
                    ->icon('heroicon-s-arrow-down-tray')
                    ->color('success')
                    ->tooltip('Export Vendor Purchases')
                    ->modalHeading('Export Vendor Purchases')
                    ->schema([
                        Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'excel' => 'Excel (.xlsx)',
                                'pdf'   => 'PDF (.pdf)',
                            ])
                            ->default('excel')
                            ->required()
                            ->native(false),
                    ])
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('reports.view', Filament::getCurrentPanel()->getAuthGuard()))
                    ->action(function (array $data, Vendor $record) {
                        $user = Filament::auth()->user();

                        $merchantId = match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                               => null,
                        };

                        $baseQuery = Purchase::query()
                            ->withoutTrashed()
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

                        $totals['total_amount'] = (float) $totals['total'];
                        $totals['amount_pending'] = (float) (clone $baseQuery)->sum('due_amount');
                        $totals['amount_paid'] = (float) (clone $baseQuery)->sum('paid_amount');

                        $safeName = Str::slug($record->name ?? 'vendor');
                        $timestamp = now()->format('Y-m-d_H-i-s');
                        $format = $data['format'] ?? 'excel';

                        if ($format === 'pdf') {
                            $purchases = (clone $exportQuery)
                                ->orderByDesc('purchase_date')
                                ->get();

                            $pdfContent = Pdf::loadView('exports.vendor-purchases-pdf', [
                                'vendor' => $record,
                                'purchases' => $purchases,
                                'totals' => $totals,
                            ])
                                ->setPaper('a4', 'landscape')
                                ->output();

                            return response()->streamDownload(
                                fn () => print($pdfContent),
                                "vendor-purchases-{$safeName}-{$timestamp}.pdf",
                                ['Content-Type' => 'application/pdf']
                            );
                        }

                        return Excel::download(
                            new VendorPurchasesExport($exportQuery, $totals),
                            "vendor-purchases-{$safeName}-{$timestamp}.xlsx"
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
