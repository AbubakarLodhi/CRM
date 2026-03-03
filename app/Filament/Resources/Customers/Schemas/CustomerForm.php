<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use App\Models\Country;
use App\Models\City;
use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

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
                ->default('+92')
                ->placeholder('+923001234567')
                ->helperText('Enter number with country code, e.g. +923001234567')
                ->regex('/^\+92\d{10}$/')
                ->maxLength(15)
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                    $livewire->resetValidation('data.phone');
                    $livewire->resetErrorBag('data.phone');
                }),

            Textarea::make('address')
                ->label('Address')
                ->maxLength(255)
                ->columnSpanFull()
                ->nullable(),

            TextInput::make('email')
                ->label('Email address')
                ->email()
                ->maxLength(255)
                ->unique(
                    Customer::class,
                    'email',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->withoutTrashed()
                )
                ->nullable()
                ->live()
                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                    $livewire->resetValidation('data.email');
                    $livewire->resetErrorBag('data.email');
                }),


            Hidden::make('postal_code')
                ->default('54000')
                ->dehydrateStateUsing(fn ($state) => $state ?: '54000'),
            Select::make('country_id')
                ->label('Country')
                ->relationship('country', 'name')
                ->default(fn () => Country::query()->where('code', 'PK')->value('id'))
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
                ->createOptionForm([
                    Select::make('country_id')
                        ->label('Country')
                        ->relationship('country', 'name')
                        ->default(fn (callable $get) => $get('../../country_id') ?: Country::query()->where('code', 'PK')->value('id'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    TextInput::make('name')
                        ->label('City Name')
                        ->required()
                        ->maxLength(255),
                ])
                ->createOptionUsing(function (array $data): string {
                    return City::query()->firstOrCreate(
                        [
                            'country_id' => $data['country_id'],
                            'name' => trim((string) $data['name']),
                        ]
                    )->getKey();
                })
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
            TextInput::make('occupation')
                ->label('Occupation')
                ->maxLength(255)
                ->nullable(),
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
