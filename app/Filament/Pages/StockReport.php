<?php

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Models\Product;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StockReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|\UnitEnum|null $navigationGroup = 'Reportings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Stock Report';

    protected static ?string $navigationLabel = 'Stock Report';

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->query(
                Product::query()
                    ->select([
                        'products.*',
                        DB::raw('COALESCE((
                            SELECT SUM(quantity) 
                            FROM purchase_items 
                            WHERE purchase_items.product_id = products.id
                        ), 0) as total_purchased'),
                        DB::raw('COALESCE((
                            SELECT SUM(quantity) 
                            FROM sale_items 
                            WHERE sale_items.product_id = products.id
                        ), 0) as total_sold'),
                    ])
                    ->when(! $user instanceof Admin, fn (Builder $query) => $query->where('products.merchant_id', $user->id))
                    ->where('products.is_active', true)
                    ->where('products.track_inventory', true)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_purchased')
                    ->label('Total Purchased')
                    ->numeric()
                    ->sortable()
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make(),
                    ]),

                TextColumn::make('total_sold')
                    ->label('Total Sold')
                    ->numeric()
                    ->sortable()
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make(),
                    ]),

                TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->state(fn (Product $record): int => (int) $record->total_purchased - (int) $record->total_sold)
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state <= 0 ? 'danger' : ($state <= 10 ? 'warning' : 'success'))
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make(),
                    ]),

                TextColumn::make('unit')
                    ->label('Unit')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('purchase_price')
                    ->label('Purchase Price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->relationship('category', 'name', modifyQueryUsing: function (Builder $query) use ($user) {
                        if (! $user instanceof Admin) {
                            $query->where('merchant_id', $user->id);
                        }
                    })
                    ->label('Category')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('brand_id')
                    ->relationship('brand', 'name', modifyQueryUsing: function (Builder $query) use ($user) {
                        if (! $user instanceof Admin) {
                            $query->where('merchant_id', $user->id);
                        }
                    })
                    ->label('Brand')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->options([
                        'stock' => 'Stock',
                        'service' => 'Service',
                        'measured_stock' => 'Measured Stock',
                        'custom' => 'Custom',
                    ])
                    ->label('Product Type'),
            ])
            ->defaultSort('current_stock', 'asc');
    }
}
