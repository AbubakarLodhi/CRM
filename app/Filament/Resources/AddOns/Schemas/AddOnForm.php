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
                    ->relationship('brandModel', 'name')
                    ->preload()
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set,$livewire) {
                        if ($state) {
                            $brandModel = BrandModel::find($state);
                            if ($brandModel) {
                                $set('merchant_id', $brandModel->merchant_id);
                            }
                        }
                        $livewire->resetValidation('data.brand_model_id');
                        $livewire->resetErrorBag('data.brand_model_id');
                    }),

                Hidden::make('merchant_id')->required(),
            ]);
    }
}

