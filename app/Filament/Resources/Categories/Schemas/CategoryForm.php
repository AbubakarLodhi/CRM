<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
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
                    ->maxLength(255)
                    ->required(),
                Select::make('parent_id')
                    ->label('Global Category')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Filament::auth()->user();

                            $query->whereNull('parent_id');
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
                    ->visibility('public')
                    ->imagePreviewHeight(120)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->visible(fn ($get) => blank($get('parent_id')))
                    ->dehydrated(false),


                Hidden::make('merchant_id')
                    ->default(fn () => self::resolveMerchantId()),
            ]);
    }

    private static function resolveMerchantId(): ?string
    {
        $user = Filament::auth()->user();

        return match (true) {
            $user instanceof \App\Models\Merchant => $user->id,
            $user instanceof \App\Models\User     => $user->merchant_id,
            default => null,
        };
    }
}
