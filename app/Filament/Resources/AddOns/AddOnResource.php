<?php

namespace App\Filament\Resources\AddOns;

use App\Filament\Resources\AddOns\Pages\CreateAddOn;
use App\Filament\Resources\AddOns\Pages\EditAddOn;
use App\Filament\Resources\AddOns\Pages\ListAddOns;
use App\Filament\Resources\AddOns\Schemas\AddOnForm;
use App\Filament\Resources\AddOns\Tables\AddOnsTable;
use App\Models\AddOn;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
class AddOnResource extends Resource
{
    protected static ?string $model = AddOn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'AddOn';

    protected static string | UnitEnum | null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 5;

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
        return AddOnForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AddOnsTable::configure($table);
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
            'index' => ListAddOns::route('/'),
            'create' => CreateAddOn::route('/create'),
            'edit' => EditAddOn::route('/{record}/edit'),
        ];
    }
}
