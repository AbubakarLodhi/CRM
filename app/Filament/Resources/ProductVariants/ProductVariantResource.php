<?php

namespace App\Filament\Resources\ProductVariants;

use App\Filament\Resources\ProductVariants\Pages\CreateVariant;
use App\Filament\Resources\ProductVariants\Pages\EditVariant;
use App\Filament\Resources\ProductVariants\Pages\ListVariants;
use App\Filament\Resources\ProductVariants\Schemas\VariantForm;
use App\Filament\Resources\ProductVariants\Tables\VariantsTable;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $recordTitleAttribute = 'name';
    protected static string | UnitEnum | null $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 4;

//    public static function canViewAny(): bool
//    {
//        $user = Filament::auth()->user();
//
//        return $user?->hasPermissionTo(
//            'products.view',
//            Filament::getCurrentPanel()->getAuthGuard()
//        ) ?? false;
//    }

    public static function form(Schema $schema): Schema
    {
        return VariantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VariantsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListVariants::route('/'),
            'create' => CreateVariant::route('/create'),
            'edit'   => EditVariant::route('/{record}/edit'),
        ];
    }
}
