<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffsSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $zgn = Merchant::where('email', 'info@zgngreenpvt.com')->first();
        $halaynoor = Merchant::where('email', 'info@halaynoor.com')->first();

        if ($zgn) {
            $this->createUser(
                merchant: $zgn,
                name: 'ZGN Admin User',
                email: 'admin@zgngreenpvt.com',
                roleName: 'Admin'
            );

            $this->createUser(
                merchant: $zgn,
                name: 'ZGN Supervisor',
                email: 'supervisor@zgngreenpvt.com',
                roleName: 'Supervisor'
            );
        }

        if ($halaynoor) {
            $this->createUser(
                merchant: $halaynoor,
                name: 'Halaynoor Admin',
                email: 'admin@halaynoor.com',
                roleName: 'Admin'
            );

            $this->createUser(
                merchant: $halaynoor,
                name: 'Halaynoor Support',
                email: 'support@halaynoor.com',
                roleName: 'Support Admin'
            );
        }
    }

    /**
     * @param Merchant $merchant
     * @param string $name
     * @param string $email
     * @param string $roleName
     * @return void
     */
    private function createUser(
        Merchant $merchant,
        string   $name,
        string   $email,
        string   $roleName
    ): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'id' => Str::uuid(),
                'name' => $name,
                'merchant_id' => $merchant->id,
                'password' => Hash::make('DD@2025@DD'),
                'status' => 'verified',
                'is_active' => true,
            ]
        );

        $role = Role::where('name', $roleName)->where('guard_name', 'merchant')->first();
        if ($role) $user->assignRole($role);
    }
}
