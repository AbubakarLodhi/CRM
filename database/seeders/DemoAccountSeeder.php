<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\PermissionModule;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('demo.email');
        $password = config('demo.password');
        $name = config('demo.merchant_name');

        $merchant = Merchant::query()->firstOrCreate(
            ['email' => $email],
            [
                'id' => Str::uuid()->toString(),
                'name' => $name,
                'phone' => null,
                'address_line_1' => 'Demo City',
                'city' => 'Karachi',
                'website' => config('branding.primary_merchant_website'),
                'status' => Merchant::STATUS_VERIFIED,
                'is_active' => true,
                'password' => $password,
            ]
        );

        $merchant->forceFill([
            'name' => $name,
            'status' => Merchant::STATUS_VERIFIED,
            'is_active' => true,
            'password' => $password,
        ])->save();

        $role = Role::query()
            ->where('name', 'Admin')
            ->where('guard_name', 'merchant')
            ->first();

        if ($role) {
            $merchant->syncRoles([$role]);
        }

        $this->syncPermissionModules($merchant);

        $demoSeeder = new DemoSeeder;
        $demoSeeder->setCommand($this->command);
        $demoSeeder->forMerchant($email);
    }

    private function syncPermissionModules(Merchant $merchant): void
    {
        $moduleIds = PermissionModule::query()->pluck('id');

        if ($moduleIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($moduleIds as $moduleId) {
            $exists = DB::table('merchant_permission_modules')
                ->where('merchant_id', $merchant->id)
                ->where('permission_module_id', $moduleId)
                ->exists();

            if ($exists) {
                continue;
            }

            $rows[] = [
                'id' => Str::uuid()->toString(),
                'merchant_id' => $merchant->id,
                'permission_module_id' => $moduleId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('merchant_permission_modules')->insert($rows);
        }
    }
}
