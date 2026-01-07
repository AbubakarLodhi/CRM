<?php

namespace App\Filament\Resources\Businesses\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BusinessForm
{

    public static function configure(Schema $schema): Schema
    {

        $user = Filament::auth()->user();
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                FileUpload::make('business_logo')
                    ->label('Business Logo')
                    ->image()
                    ->disk('public')
                    ->directory('brands/logos')
                    ->imagePreviewHeight(120)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->dehydrated(false),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('country_id')
                    ->label('Country')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('city_id', null)),

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
                    ->required(),

                TextInput::make('postal_code')
                    ->label('Postal Code')
                    ->placeholder('e.g. 54000')
                    ->maxLength(12)
                    ->required(),


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
