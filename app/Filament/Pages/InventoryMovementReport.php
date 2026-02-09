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
use Illuminate\Pagination\LengthAwarePaginator;
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
            ->records(fn (int|string $page = 1, int|string|null $recordsPerPage = null) => $this->getPaginatedRecords($page, $recordsPerPage))
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

                TextColumn::make('variant_name')
                    ->label('Variant')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('product_sku')
                    ->label('SKU')
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->toggleable()
                    ->numeric()
                    ->sortable(),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('PKR')
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('PKR')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('direction')
                    ->toggleable()
                    ->badge()
                    ->getStateUsing(fn ($record) =>
                    $record['type'] === 'Purchase' ? 'In' : 'Out'
                    )
                    ->color(fn ($state) =>
                    $state === 'In' ? 'success' : 'danger'
                    ),


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

        $purchaseRows = \App\Models\Purchase::query()
            ->with([
                'items.variants.variant.product',
                'items.business.users',
                'items.branch.users',
            ])
            ->when($merchantId, fn ($q) =>
            $q->where('merchant_id', $merchantId)
            )
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereHas('items.business.users', fn ($u) =>
            $u->where('users.id', $user->id)
            )
                ->whereHas('items.branch.users', fn ($u) =>
                $u->where('users.id', $user->id)
                )
            )
            ->get()
            ->flatMap(function ($purchase) {
                return $purchase->items->flatMap(function ($item) use ($purchase) {
                    return $item->variants->map(function ($variantRow) use ($purchase, $item) {

                        $variant = $variantRow->variant;
                        $product = $variant->product;

                        return [
                            'id'            => 'purchase-var-' . $variantRow->id,
                            'date'          => $purchase->purchase_date,
                            'type'          => 'Purchase',
                            'reference'     => $purchase->purchase_no,

                            // PRODUCT (BLUEPRINT)
                            'product_name'  => $product->name,

                            // VARIANT (STOCK UNIT)
                            'variant_name'  => $variant->name,
                            'product_sku'   => $variant->sku,

                            'quantity'      => $variantRow->quantity,
                            'unit_price'    => $variantRow->unit_price,
                            'total'         => $variantRow->line_total,

                            'direction'     => 'in',
                        ];
                    });
                });
            });




        // Sales (OUT)

        $saleRows = \App\Models\Sale::query()
            ->with([
                'items.variants.variant.product',
                'items.business.users',
                'items.branch.users',
            ])
            ->when($merchantId, fn ($q) =>
            $q->where('merchant_id', $merchantId)
            )
            ->when($user instanceof \App\Models\User, fn ($q) =>
            $q->whereHas('items.business.users', fn ($u) =>
            $u->where('users.id', $user->id)
            )
                ->whereHas('items.branch.users', fn ($u) =>
                $u->where('users.id', $user->id)
                )
            )
            ->get()
            ->flatMap(function ($sale) {
                return $sale->items->flatMap(function ($item) use ($sale) {
                    return $item->variants->map(function ($variantRow) use ($sale, $item) {

                        $variant = $variantRow->variant;
                        $product = $variant->product;

                        return [
                            'id'            => 'sale-var-' . $variantRow->id,
                            'date'          => $sale->sale_date,
                            'type'          => 'Sale',
                            'reference'     => $sale->sale_no,

                            'product_name'  => $product->name,
                            'variant_name'  => $variant->name,
                            'product_sku'   => $variant->sku,

                            'quantity'      => $variantRow->quantity,
                            'unit_price'    => $variantRow->unit_price,
                            'total'         => $variantRow->line_total,

                            'direction'     => 'out',
                        ];
                    });
                });
            });



        $records = $purchaseRows
            ->concat($saleRows)
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

    protected function getPaginatedRecords(int|string $page = 1, int|string|null $recordsPerPage = null): LengthAwarePaginator|Collection
    {
        $records = $this->getRecords();

        if ($recordsPerPage === null || $recordsPerPage === 'all') {
            return $records;
        }

        $page = max(1, (int) $page);
        $perPage = max(1, (int) $recordsPerPage);
        $results = $records->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $records->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
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
