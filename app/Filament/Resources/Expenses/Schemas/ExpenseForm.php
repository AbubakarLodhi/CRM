<?php

namespace App\Filament\Resources\Expenses\Schemas;

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

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Expense Information')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('expense_no')
                        ->label('Expense Number')
                        ->default(fn() => 'EXP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    DatePicker::make('expense_date')
                        ->label('Expense Date')
                        ->default(now())
                        ->required()
                        ->displayFormat('d/m/Y'),


                    Hidden::make('merchant_id')
                        ->default(fn () => match (true) {
                            Filament::auth()->user() instanceof \App\Models\Merchant
                            => Filament::auth()->user()->id,
                            Filament::auth()->user() instanceof \App\Models\User
                            => Filament::auth()->user()->merchant_id,
                            default => null,
                        })
                        ->required(),


                    Select::make('business_id')
                        ->label('Business')
                        ->relationship(
                            'business',
                            'name',
                            function (Builder $query) {
                                $user = Filament::auth()->user();

                                $merchantId = match (true) {
                                    $user instanceof \App\Models\Merchant => $user->id,
                                    $user instanceof \App\Models\User     => $user->merchant_id,
                                    default                               => null,
                                };

                                if (! $merchantId) {
                                    $query->whereRaw('1 = 0');
                                    return;
                                }

                                $query->where('merchant_id', $merchantId);

                                // 🔵 Staff → assigned businesses only
                                if ($user instanceof \App\Models\User) {
                                    $query->whereHas('users', fn ($q) =>
                                    $q->where('users.id', $user->id)
                                    );
                                }
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('branch_id', null)),


        Select::make('branch_id')
                        ->label('Branch')
                        ->relationship(
                            'branch',
                            'name',
                            function (Builder $query, callable $get) {
                                $user = Filament::auth()->user();
                                $businessId = $get('business_id');

                                if (! $businessId) {
                                    $query->whereRaw('1 = 0');
                                    return;
                                }

                                $merchantId = match (true) {
                                    $user instanceof \App\Models\Merchant => $user->id,
                                    $user instanceof \App\Models\User     => $user->merchant_id,
                                    default                               => null,
                                };

                                if (! $merchantId) {
                                    $query->whereRaw('1 = 0');
                                    return;
                                }

                                $query
                                    ->where('merchant_id', $merchantId)
                                    ->where('business_id', $businessId);

                                // 🔵 Staff → assigned branches only
                                if ($user instanceof \App\Models\User) {
                                    $query->whereHas('users', fn ($q) =>
                                    $q->where('users.id', $user->id)
                                    );
                                }
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->required(),


        Hidden::make('created_by')
                            ->default(fn() => Filament::auth()->id()),
                ]),

            Section::make('Expense Items')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('items')
                        ->schema([
                            TextInput::make('description')
                                ->label('Description')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),

                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->reactive()
                                ->debounce(300)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $unit = (float)($get('unit_price') ?? 0);
                                    $qty = (float)($state ?? 0);

                                    $set('line_total', $unit * $qty);

                                    self::recalcTotals($set, $get);
                                }),

                            TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->reactive()
                                ->debounce(300)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = (float)($get('quantity') ?? 1);
                                    $unit = (float)($state ?? 0);

                                    $set('line_total', $unit * $qty);

                                    self::recalcTotals($set, $get);
                                }),

                            TextInput::make('line_total')
                                ->label('Line Total')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->default(0),
                        ])
                        ->columns(4)
                        ->defaultItems(1)
                        ->minItems(1)
                        ->collapsible()
                        ->itemLabel(fn(array $state): string => $state['description'] ?? 'New Item')
                        ->addActionLabel('Add Item')
                        ->reorderable(false)
                        ->deletable(true)
                        ->afterStateUpdated(function (callable $set, callable $get) {
                            self::recalcTotals($set, $get);
                        }),
                ]),

            Section::make('Summary')
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Placeholder::make('subtotal_display')
                        ->label('Subtotal')
                        ->content(fn(callable $get): string => number_format((float)($get('subtotal') ?? 0), 2)),

                    TextInput::make('discount')
                        ->label('Discount')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->reactive()
                        ->debounce(300)
                        ->afterStateUpdated(fn($state, callable $set, callable $get) => self::recalcTotals($set, $get)),

                    TextInput::make('tax')
                        ->label('Tax')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->reactive()
                        ->debounce(300)
                        ->afterStateUpdated(fn($state, callable $set, callable $get) => self::recalcTotals($set, $get)),

                    Placeholder::make('total_amount_display')
                        ->label('Total Amount')
                        ->content(fn(callable $get): string => number_format((float)($get('total_amount') ?? 0), 2)),

                    Hidden::make('subtotal')->default(0)->dehydrated(),
                    Hidden::make('total_amount')->default(0)->dehydrated(),
                ]),

            Section::make('Notes')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    private static function recalcTotals(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];
        $subtotal = collect($items)->sum(fn($item) => (float)($item['line_total'] ?? 0));

        $discount = (float)($get('discount') ?? 0);
        $tax = (float)($get('tax') ?? 0);

        $set('subtotal', $subtotal);
        $set('total_amount', $subtotal - $discount + $tax);
    }
}
