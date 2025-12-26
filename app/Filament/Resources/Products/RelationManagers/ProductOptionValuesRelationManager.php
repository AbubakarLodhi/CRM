<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class ProductOptionValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected static ?string $recordTitleAttribute = 'value';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('value')
                ->label('Option Value')
                ->required()
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('value')->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }
}
