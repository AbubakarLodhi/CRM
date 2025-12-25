<?php

namespace App\Filament\Resources\Businesses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('status')
                    ->required(),
                Select::make('merchant_id')
                    ->label('Merchant')
                    ->relationship('merchant', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
            ]);
    }
}
