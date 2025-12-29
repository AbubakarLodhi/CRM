<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

use App\Models\Admin;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class VariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
//                    ->relationship('product', 'name')
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Filament::auth()->user();

                            if ($user instanceof Admin) {
                                return;
                            }

                            $query->where('merchant_id', $user->merchant_id ?? $user->id);
                        }
                    )
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
