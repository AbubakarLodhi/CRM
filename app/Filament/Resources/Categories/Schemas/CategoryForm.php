<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use App\Models\Admin;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;


class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Category Name')
                    ->required(),
                Select::make('parent_id')
                    ->label('Category')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Filament::auth()->user();

                            $query->whereNull('parent_id');
                            if ($user instanceof Admin) {
                                return;
                            }
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
                    }),
                FileUpload::make('category_icon')
                    ->label('Category Icon')
                    ->image()
                    ->disk('public')
                    ->directory('categories/icons')
                    ->imagePreviewHeight(120)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->dehydrated(false)
                    ->visible(fn ($get) => blank($get('parent_id'))),

                Select::make('merchant_id')
                    ->label('Merchant')
                    ->relationship('merchant', 'name')
                    ->visible(fn() => Filament::auth()->user() instanceof Admin),

                Hidden::make('merchant_id')
                    ->default(fn() => Filament::auth()->user()?->id)
                    ->visible(fn() => !(Filament::auth()->user() instanceof Admin)),

            ]);
    }
}
