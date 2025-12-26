<?php

namespace App\Filament\Resources\AddOns\Schemas;

use App\Models\BrandModel;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AddOnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Add-On Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('price')
                    ->label('Price')
                    ->required()
                    ->numeric()
                    ->minValue(0),

                Select::make('brand_model_id')
                    ->label('Brand Model')
                    ->relationship('model', 'name') // make sure AddOn model has correct relation 'model'
                    ->preload()
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $brandModel = BrandModel::find($state);
                            if ($brandModel) {
                                $set('merchant_id', $brandModel->merchant_id);
                            }
                        }
                    }),

                Hidden::make('merchant_id')->required(),
            ]);
    }
}
