<?php

namespace App\Filament\Resources\BrandModels\Schemas;

use App\Models\Admin;
use App\Models\Brand;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
//                    ->relationship('brand', 'name')
                    ->relationship(
                        name: 'brand',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Filament::auth()->user();

                            if ($user instanceof Admin) {
                                return;
                            }

                            $query->where('merchant_id', $user->merchant_id ?? $user->id);
                        }
                    )
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
