<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RolesResource;
use App\Models\PermissionModule;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditRoles extends EditRecord
{
    protected static string $resource = RolesResource::class;

    public function getTitle(): string
    {
        $name = (string) ($this->record?->name ?? '');

        return 'Edit ' . \Illuminate\Support\Str::limit($name, 30);
    }


    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('roles_permissions.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }
    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getEloquentQuery()->with('permissions')->findOrFail($key);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $enabledModules = PermissionModule::enabledForCurrentMerchant();

        $permissions = $this->record->permissions
            ->pluck('name')
            ->toArray();

        $permissionsData = [];

        // Initialize all modules
        foreach ($enabledModules as $module) {
            $permissionsData[$module] = [
                'enabled' => false,
                'view' => false,
                'create' => false,
                'update' => false,
                'delete' => false,
            ];
        }

        // Map existing permissions
        foreach ($permissions as $permission) {
            [$module, $action] = explode('.', $permission);

            if (isset($permissionsData[$module][$action])) {
                $permissionsData[$module][$action] = true;
                $permissionsData[$module]['enabled'] = true; // 🔑 enable module
            }
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
            'audits' => 'Audits',
        ];
    }
    protected function handleRecordUpdate(
        \Illuminate\Database\Eloquent\Model $record,
        array $data
    ): \Illuminate\Database\Eloquent\Model
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        $panelGuard = \Filament\Facades\Filament::getCurrentPanel()?->getAuthGuard();
        $data['guard_name'] = $data['guard_name'] ?? match ($panelGuard) {
            'merchant', 'staff' => 'staff',
            default => $panelGuard ?? $record->guard_name,
        };

        return DB::transaction(function () use ($record, $data, $permissions) {
            $record->update([
                'name'       => $data['name'],
                'guard_name' => $data['guard_name'] ?? $record->guard_name,
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
