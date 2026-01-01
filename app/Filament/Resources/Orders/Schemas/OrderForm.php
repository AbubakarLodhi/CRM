<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('status')
                    ->required(),
                Textarea::make('status_notes')
                    ->columnSpanFull(),
                Select::make('merchant_id')
                    ->relationship('merchant', 'name')
                    ->required(),
                Select::make('business_id')
                    ->relationship('business', 'name')
                    ->required(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required(),
                Select::make('sale_id')
                    ->relationship('sale', 'id')
                    ->required(),
            ]);
    }
}
