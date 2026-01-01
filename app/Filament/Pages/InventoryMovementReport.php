<?php

namespace App\Filament\Pages;

use App\Models\Admin;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InventoryMovementReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static string|\UnitEnum|null $navigationGroup = 'Reportings';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Inventory Movement Report';

    protected static ?string $navigationLabel = 'Inventory Movement';

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->records(function (): Collection {
                $user = Filament::auth()->user();

                // Get purchase items
                $purchaseItems = \App\Models\PurchaseItem::query()
                    ->with(['purchase', 'product'])
                    ->when(! $user instanceof Admin, fn (Builder $query) => $query->whereHas('purchase', fn ($q) => $q->where('merchant_id', $user->id))
                    )
                    ->get()
                    ->map(fn ($item) => [
                        'id' => 'purchase-'.$item->id,
                        'date' => $item->purchase->purchase_date,
                        'type' => 'Purchase',
                        'reference' => $item->purchase->purchase_no,
                        'product_name' => $item->product->name,
                        'product_sku' => $item->product->sku,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total' => $item->line_total,
                        'direction' => 'in',
                    ]);

                // Get sale items
                $saleItems = \App\Models\SaleItem::query()
                    ->with(['sale', 'product'])
                    ->when(! $user instanceof Admin, fn (Builder $query) => $query->whereHas('sale', fn ($q) => $q->where('merchant_id', $user->id))
                    )
                    ->get()
                    ->map(fn ($item) => [
                        'id' => 'sale-'.$item->id,
                        'date' => $item->sale->sale_date,
                        'type' => 'Sale',
                        'reference' => $item->sale->sale_no,
                        'product_name' => $item->product->name,
                        'product_sku' => $item->product->sku,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total' => $item->line_total,
                        'direction' => 'out',
                    ]);

                return $purchaseItems->concat($saleItems)->sortByDesc('date')->values();
            })
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Purchase' ? 'success' : 'danger'),

                TextColumn::make('reference')
                    ->label('Reference No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product_sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'in' ? 'In' : 'Out')
                    ->color(fn (string $state): string => $state === 'in' ? 'success' : 'danger')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'Purchase' => 'Purchase',
                        'Sale' => 'Sale',
                    ])
                    ->label('Type'),

                SelectFilter::make('direction')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                    ])
                    ->label('Direction'),
            ])
            ->defaultSort('date', 'desc');
    }
}
