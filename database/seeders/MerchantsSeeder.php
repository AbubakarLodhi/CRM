<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Services\Demo\DemoMerchantAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MerchantsSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = [
            [
                'email' => 'info@flowdesk.com',
                'name' => 'Flowdesk',
                'website' => 'https://flowdesk.app/',
                'password' => 'DD@2025@DD',
            ],
            [
                'email' => 'info@halaynoor.com',
                'name' => 'Halaynoor',
                'website' => 'https://halaynoor.com/',
                'password' => 'DD@2025@DD',
            ],
        ];

        $demoMerchantAccess = app(DemoMerchantAccess::class);

        foreach ($merchants as $data) {
            $merchant = Merchant::firstOrCreate(
                ['email' => $data['email']],
                [
                    'id' => Str::uuid()->toString(),
                    'name' => $data['name'],
                    'phone' => null,
                    'address_line_1' => 'Pakistan',
                    'city' => 'Karachi',
                    'website' => $data['website'],
                    'status' => Merchant::STATUS_VERIFIED,
                    'is_active' => true,
                    'password' => $data['password'],
                ]
            );

            $merchant->forceFill([
                'name' => $data['name'],
                'website' => $data['website'],
                'status' => Merchant::STATUS_VERIFIED,
                'is_active' => true,
                'password' => $data['password'],
            ])->save();

            $demoMerchantAccess->grantFullAccess($merchant);
        }
    }
}
