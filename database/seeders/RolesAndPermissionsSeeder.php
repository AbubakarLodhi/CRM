<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $modules = [
            'dashboard' => ['view', 'create', 'update', 'delete'],
            'users' => ['view', 'create', 'update', 'delete'],
            'admins' => ['view', 'create', 'update', 'delete'],
            'settings' => ['view', 'create', 'update', 'delete'],
            'roles_permissions' => ['view', 'create', 'update', 'delete'],
            'merchants' => ['view', 'create', 'update', 'delete'],
            'merchant_settings' => ['view', 'create', 'update', 'delete'],
            'orders' => ['view', 'create', 'update', 'delete'],
            'businesses' => ['view', 'create', 'update', 'delete'],
            'branches' => ['view', 'create', 'update', 'delete'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "$module.$action", 'guard_name' => "admin"]);
                Permission::firstOrCreate(['name' => "$module.$action", 'guard_name' => "staff"]);
                Permission::firstOrCreate(['name' => "$module.$action", 'guard_name' => "merchant"]);
            }
        }
        $roles = [
            'superadmin' => [
                'dashboard.*',
                'users.*',
                'admins.*',
                'settings.*',
                'roles_permissions.*',
                'merchants.*',
                'orders.*',
                'merchant_settings.*',
                'branches.*',
                'businesses.*',
            ],
            'admin' => [
                'dashboard.*',
                'users.*',
                'settings.*',
                'roles_permissions.*',
                'merchants.view',
                'orders.*',
                'merchant_settings.*',
                'branches.*',
                'businesses.*',
            ],
            'user' => [
            ],
            'merchant' => [
                'users.view',
                'settings.view',
                'merchants.view',
                'businesses.view',
                'branches.view',
            ],
        ];
        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'admin',]);

            $finalPermissions = collect($perms)->flatMap(function ($permission) use ($modules) {
                if (str_contains($permission, '.*')) {
                    $prefix = str_replace('.*', '', $permission);
                    return collect($modules[$prefix])->map(fn($act) => "$prefix.$act");
                }
                return [$permission];
            });

            $role->syncPermissions($finalPermissions);
        }
        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'staff',]);

            $finalPermissions = collect($perms)->flatMap(function ($permission) use ($modules) {
                if (str_contains($permission, '.*')) {
                    $prefix = str_replace('.*', '', $permission);
                    return collect($modules[$prefix])->map(fn($act) => "$prefix.$act");
                }
                return [$permission];
            });

            $role->syncPermissions($finalPermissions);
        }
        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'merchant',]);

            $finalPermissions = collect($perms)->flatMap(function ($permission) use ($modules) {
                if (str_contains($permission, '.*')) {
                    $prefix = str_replace('.*', '', $permission);
                    return collect($modules[$prefix])->map(fn($act) => "$prefix.$act");
                }
                return [$permission];
            });

            $role->syncPermissions($finalPermissions);
        }
        $users = Admin::all();
        foreach ($users as $user) {
            $superAdminRole = Role::where('name', 'superadmin')->where('guard_name', 'admin')->first();
            if ($superAdminRole) $user->assignRole($superAdminRole->name, 'admin');
        }
    }
}
