<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class ProductVariantValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_option_id')
                ->label('Option')
                ->relationship('option', 'display_name')
                ->required(),

            Select::make('product_option_value_id')
                ->label('Value')
                ->relationship('value', 'value')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('option.display_name')->label('Option'),
                TextColumn::make('value.value')->label('Value'),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\DeleteAction::make(),
            ]);
    }
}
