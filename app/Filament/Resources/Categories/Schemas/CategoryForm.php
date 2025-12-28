<?php

namespace App\Filament\Resources\Categories\Schemas;
use Filament\Forms\Components\FileUpload;
use App\Models\Admin;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Category Name')
                    ->required(),
                FileUpload::make('category_icon')
                    ->label('Category Icon')
                    ->image()
                    ->disk('public')
                    ->directory('categories/icons')
                    ->imagePreviewHeight(120)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->dehydrated(false),

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
