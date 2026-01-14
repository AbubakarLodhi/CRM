<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

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
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Filament::auth()->user();

                            $merchantId = match (true) {
                                $user instanceof \App\Models\Merchant => $user->id,
                                $user instanceof \App\Models\User     => $user->merchant_id,
                                default                               => null,
                            };

                            if (! $merchantId) {
                                $query->whereRaw('1 = 0');
                                return;
                            }

                            $query->where('merchant_id', $merchantId);
                        }
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set,$livewire) {
                        if (! $state) {
                            $set('merchant_id', null);
                            return;
                        }

                        $product = \App\Models\Product::find($state);
                        $set('merchant_id', $product?->merchant_id);
                        $livewire->resetValidation('data.product_id');
                        $livewire->resetErrorBag('data.product_id');
                    }),

                TextInput::make('name')
                    ->label('Variant Name')
                    ->required()
                    ->helperText('Optional (e.g. 72V / 30Ah)')
                    ->maxLength(255)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                        $livewire->resetValidation('data.name');
                        $livewire->resetErrorBag('data.name');
                    }),

//                TextInput::make('sku')
//                    ->label('SKU')
//                    ->maxLength(255),

                TextInput::make('purchase_price')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->label('Purchase Price')
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                        $livewire->resetValidation('data.purchase_price');
                        $livewire->resetErrorBag('data.purchase_price');
                    }),

                TextInput::make('selling_price')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->label('Selling Price')
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                        $livewire->resetValidation('data.selling_price');
                        $livewire->resetErrorBag('data.selling_price');
                    }),

                Hidden::make('merchant_id')
                    ->default(function () {
                        $user = Filament::auth()->user();

                        return match (true) {
                            $user instanceof \App\Models\Merchant => $user->id,
                            $user instanceof \App\Models\User     => $user->merchant_id,
                            default                               => null,
                        };
                    })
                    ->required(),

        ]);
    }
}
