<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $product = \App\Models\Product::find($state);
                            $set('merchant_id', $product?->merchant_id);
                        }
                    }),

                TextInput::make('name')
                    ->label('Variant Name')
                    ->helperText('Optional (e.g. 72V / 30Ah)')
                    ->maxLength(255),

                TextInput::make('sku')
                    ->label('SKU')
                    ->maxLength(255),

                TextInput::make('purchase_price')
                    ->numeric()
                    ->label('Purchase Price'),

                TextInput::make('selling_price')
                    ->numeric()
                    ->label('Selling Price'),

                Hidden::make('merchant_id')->required(),
            ]);
    }
}
