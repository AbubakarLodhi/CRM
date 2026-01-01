<?php

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Models\Purchase;
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

class PurchasesSummary extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = 'Reportings';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Purchases Summary';

    protected static ?string $navigationLabel = 'Purchases Summary';

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->query(
                Purchase::query()
                    ->with(['merchant', 'business', 'branch', 'items'])
                    ->when(! $user instanceof Admin, fn (Builder $query) => $query->where('merchant_id', $user->id))
            )
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
                    ->sortable()
                    ->searchable()
                    ->toggleable(fn () => $user instanceof Admin),

                TextColumn::make('business.name')
                    ->label('Business')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('USD')
                    ->sortable()
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->money('USD'),
                    ]),

                TextColumn::make('discount')
                    ->label('Discount')
                    ->money('USD')
                    ->sortable()
                    ->toggleable()
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->money('USD'),
                    ]),

                TextColumn::make('tax')
                    ->label('Tax')
                    ->money('USD')
                    ->sortable()
                    ->toggleable()
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->money('USD'),
                    ]),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold')
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->money('USD'),
                    ]),
            ])
            ->filters([
                SelectFilter::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->label('Merchant')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => $user instanceof Admin),

                SelectFilter::make('business_id')
                    ->relationship('business', 'name')
                    ->label('Business')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Branch')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('purchase_date', 'desc');
    }
}
