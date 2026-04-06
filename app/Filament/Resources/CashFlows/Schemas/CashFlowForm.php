<?php

namespace App\Filament\Resources\CashFlows\Schemas;

use App\Models\Customer;
use App\Models\Vendor;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashFlowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cash Flow')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Hidden::make('merchant_id')
                        ->default(fn () => match (true) {
                            Filament::auth()->user() instanceof \App\Models\Merchant => Filament::auth()->user()->id,
                            Filament::auth()->user() instanceof \App\Models\User => Filament::auth()->user()->merchant_id,
                            default => null,
                        })
                        ->required(),

                    Hidden::make('created_by')
                        ->default(fn () => Filament::auth()->user() instanceof \App\Models\User ? Filament::auth()->id() : null),

                    Select::make('party_type')
                        ->label('Party Type')
                        ->options([
                            Customer::class => 'Customer',
                            Vendor::class => 'Vendor',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set): void {
                            $set('party_id', null);
                        })
                        ->columnSpan(1),

                    Select::make('party_id')
                        ->label('Party')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function (callable $get): array {
                            $partyType = $get('party_type');

                            $merchantId = match (true) {
                                Filament::auth()->user() instanceof \App\Models\Merchant => Filament::auth()->user()->id,
                                Filament::auth()->user() instanceof \App\Models\User => Filament::auth()->user()->merchant_id,
                                default => null,
                            };

                            if (! $merchantId) {
                                return [];
                            }

                            if ($partyType === Customer::class) {
                                return Customer::query()
                                    ->withoutTrashed()
                                    ->where('merchant_id', $merchantId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }

                            if ($partyType === Vendor::class) {
                                return Vendor::query()
                                    ->withoutTrashed()
                                    ->where('merchant_id', $merchantId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }

                            return [];
                        })
                        ->columnSpan(2),

                    Select::make('flow_type')
                        ->label('Flow Type')
                        ->options([
                            'advance' => 'Advance',
                            'loan' => 'Loan',
                        ])
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('amount')
                        ->label('Amount')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->columnSpan(1),

                    DatePicker::make('flow_date')
                        ->label('Flow Date')
                        ->required()
                        ->default(now())
                        ->displayFormat('d/m/Y')
                        ->columnSpan(1),

                    TextInput::make('method')
                        ->label('Method')
                        ->default('Cash')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->columnSpanFull()
                        ->rows(4),
                ]),
        ]);
    }
}
