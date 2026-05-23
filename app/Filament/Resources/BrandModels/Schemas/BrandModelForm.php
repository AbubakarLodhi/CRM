<?php

namespace App\Filament\Resources\BrandModels\Schemas;
use App\Models\Brand;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class BrandModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->maxLength(255)
                    ->required()
                    ->live()
                    ->unique()
//                    ->rules([
//                        fn ($get) => Rule::unique('brand_models', 'name')
//                            ->where('brand_id', $get('brand_id'))
//                            ->ignore($get('id')), // important for edit
//                    ])
                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                        $livewire->resetValidation('data.name');
                        $livewire->resetErrorBag('data.name');
                    }),


                Select::make('brand_id')
                    ->label('Brand Name')
//                    ->relationship('brand', 'name')
                    ->relationship(
                        name: 'brand',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Filament::auth()->user();
                            $query->where('merchant_id', $user->merchant_id ?? $user->id);
                        }
                    )
                    ->preload()
                    ->searchable()
                    ->reactive()
                    ->live()// 👈 key
                    ->afterStateUpdated(function ($state, callable $set,$livewire) {
                        if (! $state) {
                            $set('merchant_id', null);
                            return;
                        }

                        $brand = Brand::find($state);
                        $set('merchant_id', $brand?->merchant_id);
                        $livewire->resetValidation('data.brand_id');
                        $livewire->resetErrorBag('data.brand_id');
                    })
                    ->required(),

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
