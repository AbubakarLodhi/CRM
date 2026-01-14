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
    /**
     * ✅ Reusable components
     * Used by:
     * - Customer Resource (Create/Edit)
     * - Sale inline modal (Create Customer)
     */
    public static function components(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),

            TextInput::make('phone')
                ->label('Phone')
                ->tel()
                ->numeric()
                ->minValue(0)
                ->maxLength(15)
                ->nullable(),

            Textarea::make('address')
                ->label('Address')
                ->maxLength(255)
                ->columnSpanFull()
                ->nullable(),

            TextInput::make('email')
                ->label('Email address')
                ->email()
                ->maxLength(255)
                ->unique(Customer::class, 'email', ignoreRecord: true)
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
                    fn ($query, callable $get) =>
                    $query->where('country_id', $get('country_id'))
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
                ->default(fn () => match (true) {
                    Filament::auth()->user() instanceof Merchant
                    => Filament::auth()->user()->id,
                    Filament::auth()->user() instanceof User
                    => Filament::auth()->user()->merchant_id,
                    default => null,
                })
                ->required(),

            TextInput::make('reference')
                ->label('Reference Customer')
                ->maxLength(255)
                ->nullable(),
        ];
    }

    /**
     * ✅ Standard Filament schema entrypoint
     * Used by CustomerResource
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }
}
