<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BrandsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('name')
                           ->required()
                           ->maxLength(255),

                FileUpload::make('brand_logo')
                    ->label('Brand Logo')
                    ->image()
                    ->disk('public')
                    ->directory('brands/logos')
                    ->imagePreviewHeight(120)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->dehydrated(false),


                Select::make('category_id')
                           ->label('Category')
                           ->relationship(
                               'category',
                               'name',
                               fn ($query) => $query->whereNotNull('parent_id')
                           )
                           ->searchable()
                           ->preload()
                           ->required()
                           ->afterStateUpdated(function ($state, callable $set) {
                               if (! $state) {
                                   $set('merchant_id', null);
                                   return;
                               }

                               $merchantId = Category::query()
                                   ->whereKey($state)
                                   ->value('merchant_id');

                               $set('merchant_id', $merchantId);
                           }),

                            Hidden::make('merchant_id')
                           ->required(),
                   ]);

    }
}
