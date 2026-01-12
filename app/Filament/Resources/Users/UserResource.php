<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\PermissionModule;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;
    protected static ?string $navigationLabel = 'Staff';
    protected static ?string $modelLabel = 'Staff';
    protected static ?string $pluralModelLabel = 'Staff';


    protected static ?int $navigationSort = 5;
    protected static ?string $recordTitleAttribute = 'name';
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        $guard=Filament::getCurrentPanel()->getAuthGuard();
//        if (! $user || $guard=='staff') {
//            return false;
//        }

        if (! PermissionModule::isEnabledForCurrentMerchant('users')) {
            return false;
        }
        return $user->hasPermissionTo(
            'users.view',
            $guard
        );
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Filament::auth()->user();
        $query = parent::getEloquentQuery();


        // Merchant can see only their businesses
        return $query->where('merchant_id', $user->merchant_id ?? $user->id);
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
