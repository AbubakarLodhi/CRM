<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
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

                Textarea::make('address')
                    ->columnSpanFull(),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(Customer::class, 'email')
                    ->required(),

                TextInput::make('postal_code')
                    ->label('Postal Code')
                    ->numeric()
                    ->rules(['digits_between:1,12'])
                    ->maxLength(20)
                    ->nullable(),

                Select::make('country_id')
                    ->label('Country')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set, $state, $old, $livewire) {
                        $set('city_id', null);
                        $livewire->resetValidation('data.country_id');
                        $livewire->resetErrorBag('data.country_id');
                    }),

                Select::make('city_id')
                    ->label('City')
                    ->relationship(
                        'city',
                        'name',
                        fn ($query, callable $get) => $query->where('country_id', $get('country_id'))
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                        $livewire->resetValidation('data.city_id');
                        $livewire->resetErrorBag('data.city_id');
                    }),


                Hidden::make('merchant_id')
                    ->default(function () {
                        $user = Filament::auth()->user();

                        if ($user instanceof Merchant) {
                            return $user->id;
                        }

                        if ($user instanceof User) {
                            return $user->merchant_id;
                        }

                        return null;
                    }),
                TextInput::make('reference')
                    ->label('Reference Customer')
                    ->nullable(),
            ]);
    }
}
