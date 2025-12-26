<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminsSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $admin = Admin::firstOrCreate(
            ['email' => 'superadmin@system.com'],
            [
                'id' => Str::uuid(),
                'name' => 'System Super Admin',
                'password' => 'DD@2025@DD',
                'status' => true,
            ]
        );

        $role = Role::where('name', 'Super Admin')
            ->where('guard_name', 'admin')
            ->first();

        if ($role) $admin->assignRole($role);

        $admin = Admin::firstOrCreate(
            ['email' => 'admin@system.com'],
            [
                'id' => Str::uuid(),
                'name' => 'System Admin',
                'password' => 'DD@2025@DD',
                'status' => true,
            ]
        );

        $role = Role::where('name', 'Admin')
            ->where('guard_name', 'admin')
            ->first();

        if ($role) $admin->assignRole($role);
    }
}
