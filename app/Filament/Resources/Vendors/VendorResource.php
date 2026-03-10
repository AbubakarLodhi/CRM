<?php

namespace App\Filament\Resources\Vendors;

use App\Filament\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Filament\Resources\Vendors\Pages\ViewVendorPurchases;
use App\Filament\Resources\Vendors\Schemas\VendorForm;
use App\Filament\Resources\Vendors\Tables\VendorsTable;
use App\Models\Vendor;
use App\Models\PermissionModule;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'Vendor';

    protected static ?int $navigationSort = 7;

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        if (! $user) {
            return false;
        }

        if (! PermissionModule::isEnabledForCurrentMerchant('vendors')) {
            return false;
        }

        return $user->hasPermissionTo('vendors.view', $guard);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Filament::auth()->user();
        $query = parent::getEloquentQuery();

        if ($user instanceof \App\Models\Merchant) {
            return $query->where('merchant_id', $user->id);
        }

        if ($user instanceof \App\Models\User) {
            return $query->where('merchant_id', $user->merchant_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return VendorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'purchases' => ViewVendorPurchases::route('/{record}/purchases'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
}
