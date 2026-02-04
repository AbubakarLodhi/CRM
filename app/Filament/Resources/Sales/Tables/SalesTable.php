<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('sale_no')
                    ->label('Sale No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sale_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),


                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('PKR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('discount')
                    ->label('Discount (%)')
                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 2) . '%')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tax')
                    ->label('Tax (%)')
                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 2) . '%')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('PKR')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer')
                    ->searchable()
                    ->preload(),

            ])
            ->recordUrl(fn (Sale $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('sales.update', Filament::getCurrentPanel()->getAuthGuard())
                ? SaleResource::getUrl('edit', ['record' => $record])
                : null
            )
            ->recordActions([
                ViewAction::make()
                    ->color('info')
                    ->label('')
                    ->tooltip('View')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('sales.view', Filament::getCurrentPanel()->getAuthGuard())
                    ),

                Action::make('invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->label(' ')
                    ->tooltip('Invoice')
                    ->url(fn ($record) => route('invoices.show', [
                        'type' => 'sale',
                        'id'   => $record->id,
                    ])),
                    //->openUrlInNewTab(),


                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('sales.update', Filament::getCurrentPanel()->getAuthGuard())
                    ),

                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () =>
                    auth(Filament::getCurrentPanel()->getAuthGuard())
                        ->user()?->hasPermissionTo('sales.delete', Filament::getCurrentPanel()->getAuthGuard())
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () =>
                        auth(Filament::getCurrentPanel()->getAuthGuard())
                            ->user()?->hasPermissionTo('sales.delete', Filament::getCurrentPanel()->getAuthGuard())
                        ),
                ]),
            ])
            ->defaultSort('sale_date', 'desc');
    }
}
