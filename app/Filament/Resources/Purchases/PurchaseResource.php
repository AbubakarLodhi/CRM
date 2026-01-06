<?php

namespace App\Filament\Resources\Purchases;

use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Resources\Purchases\Pages\EditPurchase;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Resources\Purchases\Pages\ViewPurchase;
use App\Filament\Resources\Purchases\Schemas\PurchaseForm;
use App\Filament\Resources\Purchases\Schemas\PurchaseInfolist;
use App\Filament\Resources\Purchases\Tables\PurchasesTable;
use App\Models\Admin;
use App\Models\PermissionModule;
use App\Models\Purchase;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'purchase_no';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        if (! $user) {
            return false;
        }
        // 🔐 Module gate
        if (! PermissionModule::isEnabledForCurrentMerchant('purchases')) {
            return false;
        }

        // 🔐 Permission gate
        return $user->hasPermissionTo('purchases.view', $guard);
    }


    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Filament::auth()->user();
        $query = parent::getEloquentQuery();

        // Admin can see all purchases
        if ($user instanceof Admin) {
            return $query;
        }

        // Merchant can see only their purchases
        return $query->where('merchant_id', $user->id);
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchasesTable::configure($table);
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
            'index' => ListPurchases::route('/'),
            'create' => CreatePurchase::route('/create'),
            'view' => ViewPurchase::route('/{record}'),
            'edit' => EditPurchase::route('/{record}/edit'),
        ];
    }
}
