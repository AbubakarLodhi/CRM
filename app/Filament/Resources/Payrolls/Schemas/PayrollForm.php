<?php

namespace App\Filament\Resources\Payrolls\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PayrollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payroll Information')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('payroll_no')
                        ->label('Payroll Number')
                        ->default(fn () => 'PAY-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6)))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Select::make('user_id')
                        ->label('Employee')
                        ->relationship(
                            name: 'user',
                            titleAttribute: 'name',
                            modifyQueryUsing: function (Builder $query) {
                                $user = Filament::auth()->user();
                                if ($user instanceof \App\Models\Merchant) {
                                    $query->where('merchant_id', $user->id);
                                } else {
                                    $query->where('merchant_id', $user->merchant_id);
                                }
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn () => request()->get('user_id'))
                        ->disabled(fn () => request()->has('user_id'))
                        ->reactive(),

                    Select::make('period_month')
                        ->label('Month')
                        ->options([
                            1 => 'January',
                            2 => 'February',
                            3 => 'March',
                            4 => 'April',
                            5 => 'May',
                            6 => 'June',
                            7 => 'July',
                            8 => 'August',
                            9 => 'September',
                            10 => 'October',
                            11 => 'November',
                            12 => 'December',
                        ])
                        ->default(now()->month)
                        ->required()
                        ->reactive(),

                    Select::make('period_year')
                        ->label('Year')
                        ->options(function () {
                            $years = [];
                            $currentYear = now()->year;
                            for ($i = $currentYear - 2; $i <= $currentYear + 2; $i++) {
                                $years[$i] = $i;
                            }

                            return $years;
                        })
                        ->default(now()->year)
                        ->required()
                        ->reactive(),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending')
                        ->required()
                        ->reactive(),

                    DatePicker::make('payment_date')
                        ->label('Payment Date')
                        ->displayFormat('d/m/Y')
                        ->visible(fn (callable $get) => $get('status') === 'paid'),


                    Hidden::make('merchant_id')
                        ->default(fn () => Filament::auth()->user()?->id)
                        ->required(),

                ]),

            Section::make('Salary Details')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('base_salary')
                        ->label('Base Salary')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->minValue(0)
                        ->prefix('$')
                        ->reactive()
                        ->debounce(300)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            self::recalcNetSalary($set, $get);
                        }),
                ]),

            Section::make('Allowances')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('allowances')
                        ->schema([
                            TextInput::make('name')
                                ->label('Allowance Name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('amount')
                                ->label('Amount')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->prefix('$')
                                ->reactive()
                                ->debounce(300)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    self::recalcNetSalary($set, $get);
                                }),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => ($state['name'] ?? '').' - $'.number_format((float) ($state['amount'] ?? 0), 2))
                        ->addActionLabel('Add Allowance')
                        ->reorderable(false)
                        ->deletable(true)
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            self::recalcNetSalary($set, $get);
                        }),
                ]),

            Section::make('Deductions')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('deductions')
                        ->schema([
                            TextInput::make('name')
                                ->label('Deduction Name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('amount')
                                ->label('Amount')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->prefix('$')
                                ->reactive()
                                ->debounce(300)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    self::recalcNetSalary($set, $get);
                                }),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => ($state['name'] ?? '').' - $'.number_format((float) ($state['amount'] ?? 0), 2))
                        ->addActionLabel('Add Deduction')
                        ->reorderable(false)
                        ->deletable(true)
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            self::recalcNetSalary($set, $get);
                        }),
                ]),

            Section::make('Summary')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Placeholder::make('base_salary_display')
                        ->label('Base Salary')
                        ->content(fn (callable $get): string => '$'.number_format((float) ($get('base_salary') ?? 0), 2)),
                    Placeholder::make('total_allowances_display')
                        ->label('Total Allowances')
                        ->content(fn (callable $get): string => '$'.number_format(self::calculateTotal($get('allowances') ?? []), 2)),
                    Placeholder::make('total_deductions_display')
                        ->label('Total Deductions')
                        ->content(fn (callable $get): string => '$'.number_format(self::calculateTotal($get('deductions') ?? []), 2)),
                    Placeholder::make('net_salary_display')
                        ->label('Net Salary')
                        ->content(fn (callable $get): string => '$'.number_format((float) ($get('net_salary') ?? 0), 2))
                        ->columnSpanFull()
                        ->extraAttributes(['class' => 'text-lg font-bold']),
                    Hidden::make('net_salary')->default(0)->dehydrated(),
                ]),

            Textarea::make('notes')
                ->label('Notes')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    private static function calculateTotal(array $items): float
    {
        return collect($items)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
    }

    private static function recalcNetSalary(callable $set, callable $get): void
    {
        $baseSalary = (float) ($get('base_salary') ?? 0);
        $totalAllowances = self::calculateTotal($get('allowances') ?? []);
        $totalDeductions = self::calculateTotal($get('deductions') ?? []);
        $netSalary = $baseSalary + $totalAllowances - $totalDeductions;
        $set('net_salary', $netSalary);
    }
}
