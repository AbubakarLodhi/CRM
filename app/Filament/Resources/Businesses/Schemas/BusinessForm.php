<?php

namespace App\Filament\Resources\Businesses\Schemas;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class BusinessForm
{

    public static function configure(Schema $schema): Schema
    {

        $user = Filament::auth()->user();
        return $schema
            ->components([
                TextInput::make('name')
                    ->maxLength(250)
                    ->required(),
                FileUpload::make('business_logo')
                    ->label('Business Logo')
                    ->image()
                    ->disk('public')
                    ->directory('business/logos')
                    ->visibility('public')
                    ->imagePreviewHeight(120)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->dehydrated(false),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->maxLength(400),
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

                Grid::make(2)
                    ->schema([
                        TextInput::make('postal_code')
                            ->label('Postal Code')
                            ->placeholder('e.g. 54000')
                            ->numeric()
                            ->minLength(5)
                            ->minValue(0)
                            ->rules(['digits_between:1,12'])
                            ->maxLength(12)
                            ->required(),



                    ])
                    ->columnSpanFull()
                    ->columns(2),


                    Toggle::make('status')
                        ->required(),
                Hidden::make('merchant_id')
                    ->default(fn () =>
                    $user instanceof \App\Models\User
                        ? $user->merchant_id
                        : $user?->id
                    )
            ]);


    }
}
