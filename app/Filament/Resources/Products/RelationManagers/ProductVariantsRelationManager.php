<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class ProductVariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Variant Name')
                ->helperText('Optional (e.g. 72V / 30Ah)')
                ->maxLength(255),

            TextInput::make('sku')
                ->required()
                ->maxLength(255),

            TextInput::make('purchase_price')
                ->numeric()
                ->nullable(),

            TextInput::make('selling_price')
                ->numeric()
                ->nullable(),

            Hidden::make('merchant_id')
                ->default(fn ($livewire) => $livewire->ownerRecord->merchant_id),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('sku')->searchable(),
                TextColumn::make('selling_price')->money()->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    // ProductVariantsRelationManager.php

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Products\RelationManagers\ProductVariantValuesRelationManager::class,
        ];
    }

}
