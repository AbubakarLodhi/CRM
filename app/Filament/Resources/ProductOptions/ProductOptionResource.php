<?php

namespace App\Filament\Resources\ProductOptions;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\ProductOptionValuesRelationManager;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductOptionResource extends Resource
{
    protected static ?string $model = \App\Models\ProductOption::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Inventory';

    public static function form($schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->label('Option Key'),

            TextInput::make('display_name')
                ->label('Display Name'),

            Select::make('product_id')
                ->relationship('product', 'name')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('display_name'),
            TextColumn::make('product.name')->label('Product'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductOptionValuesRelationManager::class, // ✅ THIS is where values live
        ];
    }
}
