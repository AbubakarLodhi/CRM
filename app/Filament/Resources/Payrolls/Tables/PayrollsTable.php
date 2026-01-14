<?php

namespace App\Filament\Resources\Payrolls\Tables;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Payroll;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayrollsTable
{
    public static function configure(Table $table): Table
    {
        $user = Filament::auth()->user();
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return $table
            ->columns([
                TextColumn::make('payroll_no')
                    ->label('Payroll No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('period_display')
                    ->label('Period')
                    ->formatStateUsing(fn ($record) => $record->period_month.'/'.$record->period_year)
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('period_year', $direction)
                            ->orderBy('period_month', $direction);
                    }),

                TextColumn::make('base_salary')
                    ->label('Base Salary')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('total_allowances')
                    ->label('Allowances')
                    ->formatStateUsing(fn ($record) => '$'.number_format(collect($record->allowances ?? [])->sum(fn ($item) => (float) ($item['amount'] ?? 0)), 2))
                    ->toggleable(),

                TextColumn::make('total_deductions')
                    ->label('Deductions')
                    ->formatStateUsing(fn ($record) => '$'.number_format(collect($record->deductions ?? [])->sum(fn ($item) => (float) ($item['amount'] ?? 0)), 2))
                    ->toggleable(),

                TextColumn::make('net_salary')
                    ->label('Net Salary')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->searchable(),

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
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Employee')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('period_year')
                    ->label('Year')
                    ->options(function () {
                        $years = [];
                        $currentYear = now()->year;
                        for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                            $years[$i] = $i;
                        }

                        return $years;
                    }),

            ])
            ->recordUrl(fn (Payroll $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('payrolls.update', Filament::getCurrentPanel()->getAuthGuard())
                ? PayrollResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )

            ->recordActions([
                ViewAction::make()
                    ->color('info')
                    ->label('')
                    ->tooltip('View'),
                    //->visible(fn () => auth($guard)->user()?->hasPermissionTo('payrolls.view', $guard)),
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit'),
                  //  ->visible(fn () => auth($guard)->user()?->hasPermissionTo('payrolls.update', $guard)),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete'),
                  //  ->visible(fn () => auth($guard)->user()?->hasPermissionTo('payrolls.delete', $guard)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth($guard)->user()?->hasPermissionTo('payrolls.delete', $guard)),
                ]),
            ])
            ->defaultSort('period_year', 'desc')
            ->defaultSort('period_month', 'desc');
    }
}
