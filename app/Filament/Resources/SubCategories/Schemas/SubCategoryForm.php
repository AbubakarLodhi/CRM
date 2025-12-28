<?php

namespace App\Filament\Resources\SubCategories\Schemas;

use App\Models\Admin;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SubCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Sub-Category Name')
                    ->required(),

                Select::make('parent_id')
                    ->label('Category')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Filament::auth()->user();

                            $query->whereNull('parent_id');

                            // Admin → see all merchants’ categories
                            if ($user instanceof Admin) {
                                return;
                            }

                            // Merchant → only own categories
                            $query->where('merchant_id', $user->merchant_id ?? $user->id);
                        }
                    )
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (! $state) {
                            $set('merchant_id', null);
                            return;
                        }

                        $parent = \App\Models\Category::find($state);
                        $set('merchant_id', $parent?->merchant_id);
                    })
                    ->required(),

                Hidden::make('merchant_id'),
            ]);
    }
}
