<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Admin;
use App\Models\Category;
use App\Models\PermissionModule;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Category';

    protected static string | UnitEnum | null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 0;

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        if (! $user) {
            return false;
        }

        // 🔐 Module gate
        if (! PermissionModule::isEnabledForCurrentMerchant('categories')) {
            return false;
        }

        // 🔐 Permission gate
        return $user->hasPermissionTo('categories.view', $guard);
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    /**
     * ✅ ONLY merchant / admin scoping
     * ❌ NO parent_id logic here
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Filament::auth()->user();

        $query = parent::getEloquentQuery();


        return $query->where('merchant_id', $user->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
