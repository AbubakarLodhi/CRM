<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                TextInput::make('phone')
                    ->tel(),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(Customer::class, 'email')
                    ->required(),

                Textarea::make('city')
                    ->label('City')
                    ->required(),

                Select::make('merchant_id')
                    ->label('Merchant')
                    ->relationship('merchant', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('reference_id')
                    ->label('Reference Customer')
                    ->relationship('reference', 'name')
                    ->options(function ($get) {
                        $currentId = $get('id');
                        return Customer::when($currentId, function ($query) use ($currentId) {
                            $query->where('id', '<>', $currentId); // exclude itself
                        })
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }
}
