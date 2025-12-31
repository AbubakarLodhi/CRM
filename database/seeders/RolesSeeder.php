<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $rolesPermissions = [

            // 🔴 PLATFORM LEVEL
            'Super Admin' => [
                '*',
            ],

            // 🟠 MERCHANT / SYSTEM ADMIN
            'Admin' => [
                'dashboard.*',
                'users.*',
                'orders.*',
                'sales.*',
                'purchases.*',
                'businesses.*',
                'branches.*',
                'products.*',
                'products_variants.*',
                'categories.*',
                'sub_categories.*',
                'brands.*',
                'addons.*',
                'customers.*',
                'roles_permissions.*',
                'reports.view',
                'merchants.view',
                'merchants.update',
                'merchant_settings.view',
                'merchant_settings.update',
            ],

            // 🟡 SUPPORT
            'Support Admin' => [
                'dashboard.view',
                'orders.view',
                'orders.update',
                'customers.view',
                'products.view',
                'reports.view',
                'merchants.view',
                'merchants.update',
                'merchant_settings.view',
                'merchant_settings.update',
            ],

            // 🔵 OPERATIONS
            'Supervisor' => [
                'orders.view',
                'orders.create',
                'orders.update',
                'sales.view',
                'sales.create',
                'purchases.view',
                'purchases.create',
                'customers.view',
                'products.view',
            ],

            // 🟢 FINANCE
            'Finance Admin' => [
                'reports.view',
                'sales.view',
                'purchases.view',
                'orders.view',
            ],

            // ⚪ DATA ENTRY
            'Data Entry' => [
                'products.create',
                'products.update',
                'customers.create',
                'orders.create',
            ],
        ];

        /**
         * Only seed roles for these guards
         */
        $allowedGuards = ['admin', 'merchant'];

        foreach ($rolesPermissions as $roleName => $permissions) {

            foreach ($allowedGuards as $guard) {

                /**
                 * ❌ Skip Super Admin for merchant guard
                 */
                if ($guard === 'merchant' && $roleName === 'Super Admin') {
                    continue;
                }

                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => $guard,
                ]);

                $finalPermissions = collect($permissions)->flatMap(function ($permission) use ($guard) {

                    // Super Admin → all permissions of the guard
                    if ($permission === '*') {
                        return Permission::where('guard_name', $guard)->pluck('name');
                    }

                    // Module wildcard (e.g. orders.*)
                    if (str_ends_with($permission, '.*')) {
                        $module = str_replace('.*', '', $permission);

                        return Permission::where('guard_name', $guard)
                            ->where('name', 'like', $module . '.%')
                            ->pluck('name');
                    }

                    // Single permission
                    return [$permission];
                })
                    ->unique()
                    ->values();

                $role->syncPermissions($finalPermissions);
            }
        }
    }
}
