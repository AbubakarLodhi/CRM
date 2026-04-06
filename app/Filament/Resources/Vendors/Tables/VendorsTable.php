<?php

namespace App\Filament\Resources\Vendors\Tables;

use App\Filament\Exports\VendorPurchasesExport;
use App\Filament\Resources\Vendors\VendorResource;
use App\Models\CashFlow;
use App\Models\Vendor;
use App\Models\Purchase;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
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
                        Grid::make(2)
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
                                DatePicker::make('date_from')
                                    ->label('From Date')
                                    ->native(false),
                                Select::make('columns')
                                    ->label('Columns')
                                    ->multiple()
                                    ->searchable()
                                    ->native(false)
                                    ->options(fn () => ['__all__' => 'Select All'] + VendorPurchasesExport::selectableColumns()),
                                DatePicker::make('date_to')
                                    ->label('To Date')
                                    ->native(false),
                            ]),
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
                            )
                            ->when(
                                filled($data['date_from'] ?? null),
                                fn ($q) => $q->whereDate('purchase_date', '>=', $data['date_from'])
                            )
                            ->when(
                                filled($data['date_to'] ?? null),
                                fn ($q) => $q->whereDate('purchase_date', '<=', $data['date_to'])
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
                                'items.product',
                                'items.branch',
                                'items.variants.variant',
                                'payments',
                            ]);

                        $safeName = Str::slug($record->name ?? 'vendor');
                        $timestamp = now()->format('Y-m-d_H-i-s');
                        $format = $data['format'] ?? 'excel';
                        $selectedColumns = is_array($data['columns'] ?? null)
                            ? array_values($data['columns'])
                            : [];
                        if (in_array('__all__', $selectedColumns, true)) {
                            $selectedColumns = array_keys(VendorPurchasesExport::selectableColumns());
                        }
                        $merchantLogoDataUri = null;
                        if ($merchantId && extension_loaded('gd')) {
                            $merchant = \App\Models\Merchant::query()
                                ->with('logo')
                                ->find($merchantId);
                            $logoPath = $merchant?->logo?->photo_url;

                            if (filled($logoPath) && Storage::disk('public')->exists($logoPath)) {
                                try {
                                    $absolutePath = Storage::disk('public')->path($logoPath);
                                    $contents = file_get_contents($absolutePath);
                                    if ($contents !== false) {
                                        $mime = mime_content_type($absolutePath) ?: 'image/png';
                                        $merchantLogoDataUri = 'data:' . $mime . ';base64,' . base64_encode($contents);
                                    }
                                } catch (\Throwable) {
                                    $merchantLogoDataUri = null;
                                }
                            }
                        }
                        $purchases = (clone $exportQuery)
                            ->orderBy('purchase_date')
                            ->orderBy('created_at')
                            ->get();
                        $cashFlows = CashFlow::query()
                            ->withoutTrashed()
                            ->where('merchant_id', $merchantId)
                            ->where('party_type', Vendor::class)
                            ->where('party_id', $record->id)
                            ->when(
                                filled($data['date_from'] ?? null),
                                fn ($q) => $q->whereDate('flow_date', '>=', $data['date_from'])
                            )
                            ->when(
                                filled($data['date_to'] ?? null),
                                fn ($q) => $q->whereDate('flow_date', '<=', $data['date_to'])
                            )
                            ->with(['party', 'merchant'])
                            ->orderBy('flow_date')
                            ->orderBy('created_at')
                            ->get();
                        $totals = VendorPurchasesExport::calculateTotals($purchases, $cashFlows);

                        if ($format === 'pdf') {
                            $rows = VendorPurchasesExport::buildStatementRows($purchases, $cashFlows, $selectedColumns);

                            $pdfContent = Pdf::loadView('exports.vendor-purchases-pdf', [
                                'vendor' => $record,
                                'headings' => VendorPurchasesExport::headingsFor($selectedColumns),
                                'rows' => $rows,
                                'totals' => $totals,
                                'merchantLogoDataUri' => $merchantLogoDataUri,
                                'dateFrom' => $data['date_from'] ?? null,
                                'dateTo' => $data['date_to'] ?? null,
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
                            new VendorPurchasesExport($purchases, $cashFlows, $totals, $selectedColumns),
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
