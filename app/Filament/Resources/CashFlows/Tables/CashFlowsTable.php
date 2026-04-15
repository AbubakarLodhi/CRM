<?php

namespace App\Filament\Resources\CashFlows\Tables;

use App\Filament\Resources\CashFlows\CashFlowResource;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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
                    ->label('Account Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => CashFlow::flowTypeLabel($state)),

                TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'in' ? 'success' : 'danger')
                    ->formatStateUsing(fn (?string $state) => $state === 'in' ? 'In' : 'Out'),

                TextColumn::make('transaction_type')
                    ->label('Transaction')
                    ->badge()
                    ->getStateUsing(fn (CashFlow $record): string => $record->isPrimaryTransaction() ? 'Original' : 'Settlement')
                    ->color(fn (string $state) => $state === 'Original' ? 'primary' : 'gray'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('PKR')
                    ->sortable(),

                TextColumn::make('settled_amount')
                    ->label('Settled')
                    ->money('PKR')
                    ->getStateUsing(function (CashFlow $record): float {
                        if (! $record->isPrimaryTransaction()) {
                            return 0.0;
                        }

                        return self::settledAmount($record);
                    }),

                TextColumn::make('remaining_amount')
                    ->label('Remaining')
                    ->money('PKR')
                    ->getStateUsing(function (CashFlow $record): float {
                        if (! $record->isPrimaryTransaction()) {
                            return 0.0;
                        }

                        return self::remainingAmount($record);
                    })
                    ->color(fn ($state) => (float) $state > 0 ? 'warning' : 'success'),

                TextColumn::make('method')
                    ->label('Method')
                    ->toggleable()
                    ->default('-'),

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
                    ->label('Account Type')
                    ->options(CashFlow::flowTypeLabels()),

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
                Action::make('settle')
                    ->label('')
                    ->tooltip('Settle')
                    ->icon('heroicon-s-banknotes')
                    ->color('success')
                    ->modalHeading('Settle Cash Flow')
                    ->schema([
                        DatePicker::make('flow_date')
                            ->label('Settlement Date')
                            ->required()
                            ->default(now()),
                        TextInput::make('amount')
                            ->label('Settlement Amount')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])
                    ->action(function (CashFlow $record, array $data): void {
                        if (! $record->isPrimaryTransaction()) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot settle this entry')
                                ->body('Only original cash flow entries can be settled.')
                                ->send();

                            return;
                        }

                        $remaining = self::remainingAmount($record);
                        $amount = round((float) ($data['amount'] ?? 0), 2);

                        if ($amount <= 0 || $amount > $remaining) {
                            Notification::make()
                                ->danger()
                                ->title('Invalid settlement amount')
                                ->body('Amount must be between PKR 0.01 and PKR ' . number_format($remaining, 2) . '.')
                                ->send();

                            return;
                        }

                        $direction = $record->direction === 'in' ? 'out' : 'in';
                        $installmentNo = $record->settlements()->withoutTrashed()->count() + 1;
                        $referenceNo = sprintf(
                            'CF-STL-%s-%03d',
                            strtoupper(substr((string) $record->id, 0, 8)),
                            $installmentNo
                        );

                        $authUser = Filament::auth()->user();
                        $createdBy = $authUser instanceof \App\Models\User ? (string) $authUser->getKey() : null;

                        CashFlow::query()->create([
                            'merchant_id' => $record->merchant_id,
                            'party_type' => $record->party_type,
                            'party_id' => $record->party_id,
                            'settlement_for_id' => $record->id,
                            'flow_type' => $record->flow_type,
                            'direction' => $direction,
                            'amount' => $amount,
                            'flow_date' => $data['flow_date'] ?? now()->toDateString(),
                            'method' => 'Cash',
                            'reference_no' => $referenceNo,
                            'notes' => $data['notes'] ?? null,
                            'created_by' => $createdBy,
                        ]);

                        $newRemaining = max(0, round($remaining - $amount, 2));

                        Notification::make()
                            ->success()
                            ->title('Settlement recorded')
                            ->body('PKR ' . number_format($amount, 2) . ' settled. Remaining: PKR ' . number_format($newRemaining, 2) . '.')
                            ->send();
                    })
                    ->visible(function (CashFlow $record): bool {
                        $user = auth(Filament::getCurrentPanel()->getAuthGuard())->user();
                        $canSettle = $user?->hasPermissionTo('cash_flows.create', Filament::getCurrentPanel()->getAuthGuard())
                            || $user?->hasPermissionTo('cash_flows.update', Filament::getCurrentPanel()->getAuthGuard());

                        return $canSettle && $record->isPrimaryTransaction() && self::remainingAmount($record) > 0;
                    }),

                Action::make('history')
                    ->label('')
                    ->tooltip('History')
                    ->icon('heroicon-s-clock')
                    ->color('gray')
                    ->modalHeading('Cash Flow History')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (CashFlow $record) {
                        $base = $record->settlementFor()->first() ?? $record;
                        $history = CashFlow::query()
                            ->withoutTrashed()
                            ->where(function (Builder $query) use ($base): void {
                                $query->whereKey($base->id)
                                    ->orWhere('settlement_for_id', $base->id);
                            })
                            ->orderBy('flow_date')
                            ->orderBy('created_at')
                            ->get();

                        return view('filament.resources.cash-flows.settlement-history', [
                            'base' => $base,
                            'history' => $history,
                            'settled' => self::settledAmount($base),
                            'remaining' => self::remainingAmount($base),
                        ]);
                    }),

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

    protected static function settledAmount(CashFlow $record): float
    {
        if (! $record->isPrimaryTransaction()) {
            return 0.0;
        }

        return round((float) $record->settlements()->withoutTrashed()->sum('amount'), 2);
    }

    protected static function remainingAmount(CashFlow $record): float
    {
        if (! $record->isPrimaryTransaction()) {
            return 0.0;
        }

        $remaining = round((float) $record->amount - self::settledAmount($record), 2);

        return max(0, $remaining);
    }
}
