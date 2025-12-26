<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RolesResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditRoles extends EditRecord
{
    protected static string $resource = RolesResource::class;


    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('roles_permissions.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getEloquentQuery()->with('permissions')->findOrFail($key);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permissions = $this->record->permissions->pluck('name')->toArray();

        // Initialize all modules with default false values
        $permissionsData = [];
        foreach (static::getPermissionModules() as $module => $label) {
            $permissionsData[$module] = [
                'select_all' => false,
                'view' => false,
                'create' => false,
                'update' => false,
                'delete' => false,
            ];
        }

        // Set true for existing permissions
        foreach ($permissions as $permission) {
            [$module, $action] = explode('.', $permission);
            if (isset($permissionsData[$module][$action])) {
                $permissionsData[$module][$action] = true;
            }
        }

        // Calculate select_all for each module
        foreach ($permissionsData as $module => &$moduleData) {
            $moduleData['select_all'] = $moduleData['view']
                && $moduleData['create']
                && $moduleData['update']
                && $moduleData['delete'];
        }

        $data['permissions'] = $permissionsData;

        return $data;
    }

    public static function getPermissionModules(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'users' => 'Users',
            'admins' => 'Admins',
            'settings' => 'Settings',
            'roles_permissions' => 'Roles & Permissions',
            'merchants' => 'Merchants',
            'merchant_settings' => 'Merchant Settings',
            'businesses' => 'Businesses',
            'orders' => 'Orders',
            'branches'=>'Branches',
            'categories' => 'Categories',
            'customers' => 'Customers',
        ];
    }
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        return DB::transaction(function () use ($record, $data, $permissions) {
            $record->update([
                'name' => $data['name'],
            ]);

            RolesResource::afterUpdate($record, $permissions);

            return $record;
        }, 3);
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
