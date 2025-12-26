<?php

namespace App\Filament\Resources\SubCategories\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                        'parent',
                        'name',
                        fn ($query) => $query->whereNull('parent_id')
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
