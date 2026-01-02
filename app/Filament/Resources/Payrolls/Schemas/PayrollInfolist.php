<?php

namespace App\Filament\Resources\Payrolls\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payroll Information')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('payroll_no')
                            ->label('Payroll Number'),
                        TextEntry::make('user.name')
                            ->label('Employee'),
                        TextEntry::make('period_display')
                            ->label('Period')
                            ->formatStateUsing(fn ($record) => $record->period_month.'/'.$record->period_year),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('payment_date')
                            ->label('Payment Date')
                            ->date('d/m/Y')
                            ->placeholder('Not paid yet'),
                        TextEntry::make('merchant.name')
                            ->label('Merchant'),
                        TextEntry::make('createdBy.name')
                            ->label('Created By'),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                Section::make('Salary Breakdown')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('base_salary')
                            ->label('Base Salary')
                            ->money('USD'),
                        TextEntry::make('total_allowances')
                            ->label('Total Allowances')
                            ->formatStateUsing(fn ($record) => '$'.number_format(collect($record->allowances ?? [])->sum(fn ($item) => (float) ($item['amount'] ?? 0)), 2)),
                        TextEntry::make('total_deductions')
                            ->label('Total Deductions')
                            ->formatStateUsing(fn ($record) => '$'.number_format(collect($record->deductions ?? [])->sum(fn ($item) => (float) ($item['amount'] ?? 0)), 2)),
                        TextEntry::make('net_salary')
                            ->label('Net Salary')
                            ->money('USD')
                            ->weight('bold')
                            ->columnSpanFull(),
                    ]),

                Section::make('Allowances')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('allowances')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name'),
                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->formatStateUsing(fn ($state) => '$'.number_format((float) $state, 2)),
                            ])
                            ->columns(2)
                            ->placeholder('No allowances'),
                    ]),

                Section::make('Deductions')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('deductions')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name'),
                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->formatStateUsing(fn ($state) => '$'.number_format((float) $state, 2)),
                            ])
                            ->columns(2)
                            ->placeholder('No deductions'),
                    ]),

                Section::make('Notes')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('No notes available')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
