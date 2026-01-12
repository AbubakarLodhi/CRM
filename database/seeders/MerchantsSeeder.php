<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MerchantsSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $merchants = [
            [
                'email' => 'info@zgngreenpvt.com',
                'name' => 'ZGN Green Private Limited',
                'website' => 'https://zgngreenpvt.com/',
            ],
            [
                'email' => 'info@halaynoor.com',
                'name' => 'Halaynoor',
                'website' => 'https://halaynoor.com/',
            ],
        ];

        foreach ($merchants as $data) {
            $merchant = Merchant::firstOrCreate(
                ['email' => $data['email']],
                [
                    'id' => Str::uuid(),
                    'name' => $data['name'],
                    'phone' => null,
                    'address_line_1' => 'Pakistan',
                    'city' => 'Karachi',
                    'website' => $data['website'],
                    'status' => 'verified',
                    'is_active' => true,
                    'password' => Hash::make('DD@2025@DD'),

                ]
            );

            $role = Role::where('name', 'Admin')->where('guard_name', 'merchant')->first();
            if ($role) $merchant->assignRole($role);
        }
    }
}
