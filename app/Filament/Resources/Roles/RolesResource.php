<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRoles;
use App\Filament\Resources\Roles\Pages\EditRoles;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Schemas\RolesForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RolesResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Key;


    protected static ?int $navigationSort =6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        $guard=Filament::getCurrentPanel()->getAuthGuard();
        if (! $user || $guard=='staff') {
            return false;
        }

        return $user->hasPermissionTo(
            'roles_permissions.view',
            $guard
        );
    }

    public static function form(Schema $schema): Schema
    {
        return RolesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $guardName = Filament::getCurrentPanel()->getAuthGuard();
        if ($guardName=='admin') {
            return Role::query();
        }
        return Role::query()
            ->when($guardName, fn($query) => $query->where('guard_name', 'staff'));
    }

    public static function afterCreate($record, array $data): void
    {
        static::syncPermissions($record, $data);
    }

    public static function afterUpdate($record, array $data): void
    {
        static::syncPermissions($record, $data);
    }

    protected static function syncPermissions($record, array $data)
    {
        $permissions = [];
        foreach ($data ?? [] as $module => $actions) {
            foreach ($actions as $action => $checked) {
                if ($checked && $action !== 'select_all') {
                    $name = "{$module}.{$action}";

                    $permissions[] = $name;
                }
            }
        }
        $record->syncPermissions($permissions);
    }
    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRoles::route('/create'),
            'edit' => EditRoles::route('/{record}/edit'),
        ];
    }
}
