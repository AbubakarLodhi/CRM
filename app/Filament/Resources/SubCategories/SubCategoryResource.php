<?php

namespace App\Filament\Resources\SubCategories;

use App\Filament\Resources\SubCategories\Pages\CreateSubCategory;
use App\Filament\Resources\SubCategories\Pages\EditSubCategory;
use App\Filament\Resources\SubCategories\Pages\ListSubCategories;
use App\Filament\Resources\SubCategories\Schemas\SubCategoryForm;
use App\Filament\Resources\SubCategories\Tables\SubCategoriesTable;
use App\Models\Category;
use App\Models\Role;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
class SubCategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Sub-Categories';
    protected static ?string $modelLabel = 'Sub-Categories';
    protected static ?string $pluralModelLabel = 'Sub-Categories';

    protected static string | UnitEnum | null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

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
        return SubCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubCategoriesTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('parent_id');
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
            'index' => ListSubCategories::route('/'),
            'create' => CreateSubCategory::route('/create'),
            'edit' => EditSubCategory::route('/{record}/edit'),
        ];
    }
}
