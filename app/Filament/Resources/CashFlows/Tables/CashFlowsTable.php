<?php

namespace App\Filament\Resources\CashFlows\Tables;

use App\Filament\Resources\CashFlows\CashFlowResource;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Vendor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashFlowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('flow_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('party_type')
                    ->label('Party Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        Customer::class => 'Customer',
                        Vendor::class => 'Vendor',
                        default => '-',
                    }),

                TextColumn::make('party.name')
                    ->label('Party')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                TextColumn::make('flow_type')
                    ->label('Flow Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ucfirst((string) ($state ?? '-'))),

                TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'in' ? 'success' : 'danger')
                    ->formatStateUsing(fn (?string $state) => $state === 'in' ? 'In' : 'Out'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('PKR')
                    ->sortable(),

                TextColumn::make('method')
                    ->label('Method')
                    ->toggleable()
                    ->default('-'),

                TextColumn::make('reference_no')
                    ->label('Reference')
                    ->toggleable()
                    ->default('-')
                    ->searchable(),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-'),
            ])
            ->filters([
                SelectFilter::make('party_type')
                    ->label('Party Type')
                    ->options([
                        Customer::class => 'Customer',
                        Vendor::class => 'Vendor',
                    ]),

                SelectFilter::make('flow_type')
                    ->label('Flow Type')
                    ->options([
                        'advance' => 'Advance',
                        'loan' => 'Loan',
                    ]),

                SelectFilter::make('direction')
                    ->label('Direction')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                    ]),

                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('to'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $builder, $date) => $builder->whereDate('flow_date', '>=', $date)
                        )
                        ->when(
                            $data['to'] ?? null,
                            fn (Builder $builder, $date) => $builder->whereDate('flow_date', '<=', $date)
                        )),
            ])
            ->recordUrl(fn (CashFlow $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('cash_flows.update', Filament::getCurrentPanel()->getAuthGuard())
                ? CashFlowResource::getUrl('edit', ['record' => $record])
                : null
            )
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('cash_flows.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('cash_flows.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('cash_flows.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ])
            ->defaultSort('flow_date', 'desc');
    }
}

