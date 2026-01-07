<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\PermissionModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MerchantPermissionModulesSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = Merchant::query()->pluck('id');
        $modules   = PermissionModule::query()->pluck('id');

        if ($merchants->isEmpty() || $modules->isEmpty()) {
            return;
        }

        $now = now();

        $rows = [];

        foreach ($merchants as $merchantId) {
            foreach ($modules as $moduleId) {
                // Avoid duplicates (important for re-runs)
                $exists = DB::table('merchant_permission_modules')
                    ->where('merchant_id', $merchantId)
                    ->where('permission_module_id', $moduleId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $rows[] = [
                    'id' => Str::uuid()->toString(),
                    'merchant_id' => $merchantId,
                    'permission_module_id' => $moduleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($rows)) {
            DB::table('merchant_permission_modules')->insert($rows);
        }
    }
}
