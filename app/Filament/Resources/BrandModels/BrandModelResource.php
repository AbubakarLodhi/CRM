<?php

namespace App\Filament\Resources\BrandModels;

use App\Filament\Resources\BrandModels\Pages\CreateBrandModel;
use App\Filament\Resources\BrandModels\Pages\EditBrandModel;
use App\Filament\Resources\BrandModels\Pages\ListBrandModels;
use App\Filament\Resources\BrandModels\Schemas\BrandModelForm;
use App\Filament\Resources\BrandModels\Tables\BrandModelsTable;
use App\Models\BrandModel;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BrandModelResource extends Resource
{
    protected static ?string $model = BrandModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'BrandModel';

    protected static ?string $navigationLabel = 'Models';
    protected static ?string $modelLabel = 'Models';
    protected static ?string $pluralModelLabel = 'Models';

    protected static string | UnitEnum | null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasPermissionTo(
            'categories.view',
            Filament::getCurrentPanel()->getAuthGuard()
        );
    }

    public static function form(Schema $schema): Schema
    {
        return BrandModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandModelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrandModels::route('/'),
            'create' => CreateBrandModel::route('/create'),
            'edit' => EditBrandModel::route('/{record}/edit'),
        ];
    }
}
