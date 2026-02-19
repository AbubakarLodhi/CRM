<?php

namespace App\Filament\Resources\SaleReturns\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SaleReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('merchant_id')
                    ->required(),
                Select::make('sale_id')
                    ->relationship('sale', 'id')
                    ->required(),
                TextInput::make('customer_id')
                    ->required(),
                TextInput::make('return_no')
                    ->required(),
                DatePicker::make('return_date')
                    ->required(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_discount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_tax')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('reason')
                    ->columnSpanFull(),
                TextInput::make('created_by'),
            ]);
    }
}
