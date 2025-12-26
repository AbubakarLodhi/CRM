<?php

namespace App\Filament\Resources\BrandModels\Schemas;

use App\Models\Brand;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BrandModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                Select::make('brand_id')
                    ->label('Brand Name')
                    ->relationship('brand', 'name')
                    ->preload()
                    ->searchable()
                    ->reactive() // 👈 key
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (! $state) {
                            $set('merchant_id', null);
                            return;
                        }

                        $brand = Brand::find($state);
                        $set('merchant_id', $brand?->merchant_id);
                    })
                    ->required(),

                Hidden::make('merchant_id'),
            ]);
    }
}
