<?php

namespace App\Filament\Resources\Businesses;

use App\Filament\Resources\Businesses\Pages\CreateBusiness;
use App\Filament\Resources\Businesses\Pages\EditBusiness;
use App\Filament\Resources\Businesses\Pages\ListBusinesses;
use App\Filament\Resources\Businesses\Schemas\BusinessForm;
use App\Filament\Resources\Businesses\Tables\BusinessesTable;
use App\Models\Business;
use App\Models\PermissionModule;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Briefcase;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        if (! $user) {
            return false;
        }

        // 🔐 Module gate
        if (! PermissionModule::isEnabledForCurrentMerchant('businesses')) {
            return false;
        }

        // 🔐 Permission gate
        return $user->hasPermissionTo('businesses.view', $guard);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Filament::auth()->user();
        $query = parent::getEloquentQuery();


        // Merchant → own businesses
        if ($user instanceof \App\Models\Merchant) {
            return $query->where('merchant_id', $user->id);
        }

        // Staff → ONLY assigned businesses
        if ($user instanceof \App\Models\User) {
            return $query->whereHas('users', fn ($q) =>
            $q->where('users.id', $user->id)
            );
        }

        return $query->whereRaw('1 = 0'); // safety
    }

    public static function form(Schema $schema): Schema
    {
        return BusinessForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessesTable::configure($table);
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
            'index' => ListBusinesses::route('/'),
            'create' => CreateBusiness::route('/create'),
            'edit' => EditBusiness::route('/{record}/edit'),
        ];
    }
}
