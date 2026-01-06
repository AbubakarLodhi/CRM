<?php

namespace Database\Seeders;

use App\Models\PermissionModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionsModulesSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'dashboard' => 'Dashboard',
            'users' => 'Users',
            'admins' => 'Admins',
            'settings' => 'Settings',
            'roles_permissions' => 'Roles & Permissions',
            'merchants' => 'Merchants',
            'merchant_settings' => 'Merchant Settings',
            'businesses' => 'Businesses',
            'orders' => 'Orders',
            'branches' => 'Branches',
            'customers' => 'Customers',
            'categories' => 'Categories',
            'sub_categories'=>'Sub Categories',
            'brands' => 'Brands',
            'models' => 'Models',
            'products' => 'Products',
            'products_variants' => 'Products Variants',
            'addons' => 'Addons',
            'sales' => 'Sales',
            'purchases' => 'Purchases',
            'expenses' => 'Expenses',
            'reports' => 'Reports',
            'payrolls'=>'Payrolls',
        ];

        foreach ($modules as $key => $label) {
            PermissionModule::updateOrCreate(
                ['module' => $key],
                [
                    'id' => (string) Str::uuid(),
                    'label' => $label,
                ]
            );
        }
    }
}
