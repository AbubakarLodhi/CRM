<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InventoryMovementReport extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;
    protected static string|\UnitEnum|null $navigationGroup = 'Reportings';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Inventory Movement Report';
    protected static ?string $navigationLabel = 'Inventory Movement';

    protected string $view = 'filament.pages.inventory-movement-report';

    /* ============================================================
     | FILTER STATE
     ============================================================ */

    public ?string $typeFilter = null;
    public ?string $directionFilter = null;

    /* ============================================================
     | FILTER FORM
     ============================================================ */

    protected function getFormSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Forms\Components\Select::make('typeFilter')
                    ->label('Type')
                    ->options([
                        'Purchase' => 'Purchase',
                        'Sale'     => 'Sale',
                    ])
                    ->placeholder('All')
                    ->reactive(),

                Forms\Components\Select::make('directionFilter')
                    ->label('Direction')
                    ->options([
                        'in'  => 'In',
                        'out' => 'Out',
                    ])
                    ->placeholder('All')
                    ->reactive(),
            ]),
        ];
    }

    /* ============================================================
     | TABLE
     ============================================================ */

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getRecords())
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state) => $state === 'Purchase' ? 'success' : 'danger'),

                TextColumn::make('reference')
                    ->label('Reference No.')
                    ->toggleable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product_sku')
                    ->label('SKU')
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->toggleable()
                    ->numeric()
                    ->sortable(),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('USD')
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('direction')
                    ->toggleable()
                    ->badge()
                    ->formatStateUsing(fn ($s) => $s === 'in' ? 'In' : 'Out')
                    ->color(fn ($s) => $s === 'in' ? 'success' : 'danger'),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10,25, 50, 100]);
    }

    /* ============================================================
     | DATA SOURCE (COLLECTION)
     ============================================================ */

    protected function getRecords(): Collection
    {
        $user = Filament::auth()->user();
        $merchantId = match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default                               => null,
        };

        $purchaseItems = \App\Models\PurchaseItem::query()
            ->with(['purchase', 'product'])
            ->when($merchantId, fn ($q) =>
            $q->whereHas('purchase', fn ($p) =>
            $p->where('merchant_id', $merchantId)
            )
            )
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereHas('purchase.business.users', fn ($u) =>
            $u->where('users.id', $user->id)
            )
                ->whereHas('purchase.branch.users', fn ($u) =>
                $u->where('users.id', $user->id)
                )
            )
            ->get()
            ->map(fn ($item) => [
                'id'           => 'purchase-' . $item->id,
                'date'         => $item->purchase->purchase_date,
                'type'         => 'Purchase',
                'reference'    => $item->purchase->purchase_no,
                'product_name' => $item->product->name,
                'product_sku'  => $item->product->sku,
                'quantity'     => $item->quantity,
                'unit_price'   => $item->unit_price,
                'total'        => $item->line_total,
                'direction'    => 'in',
            ]);

        // Sales (OUT)
        $saleItems = \App\Models\SaleItem::query()
            ->with(['sale', 'product'])
            ->when($merchantId, fn ($q) =>
            $q->whereHas('sale', fn ($s) =>
            $s->where('merchant_id', $merchantId)
            )
            )
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereHas('sale.business.users', fn ($u) =>
            $u->where('users.id', $user->id)
            )
                ->whereHas('sale.branch.users', fn ($u) =>
                $u->where('users.id', $user->id)
                )
            )
            ->get()
            ->map(fn ($item) => [
                'id'           => 'sale-' . $item->id,
                'date'         => $item->sale->sale_date,
                'type'         => 'Sale',
                'reference'    => $item->sale->sale_no,
                'product_name' => $item->product->name,
                'product_sku'  => $item->product->sku,
                'quantity'     => $item->quantity,
                'unit_price'   => $item->unit_price,
                'total'        => $item->line_total,
                'direction'    => 'out',
            ]);


        $records = $purchaseItems
            ->concat($saleItems)
            ->sortByDesc('date')
            ->values();

        // APPLY FILTERS MANUALLY
        if ($this->typeFilter) {
            $records = $records->where('type', $this->typeFilter)->values();
        }

        if ($this->directionFilter) {
            $records = $records->where('direction', $this->directionFilter)->values();
        }

        return $records;
    }

    /* ============================================================
     | STATS (FILTER AWARE)
     ============================================================ */

    public function getMovementStats(): array
    {
        $records = $this->getRecords();

        $in  = $records->where('direction', 'in')->sum('quantity');
        $out = $records->where('direction', 'out')->sum('quantity');

        return [
            'in'  => (float) $in,
            'out' => (float) $out,
            'net' => (float) ($in - $out),
        ];
    }
}
