<?php

namespace Database\Seeders\ZGN;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNBrandsSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@zgngreenpvt.com')->first();
        if (!$merchant) return;

        /**
         * Helper: find category by name (merchant scoped)
         */
        $category = function (string $name) use ($merchant) {
            return Category::where('merchant_id', $merchant->id)
                ->where('name', $name)
                ->first();
        };

        /**
         * Helper: create brand
         */
        $create = function (string $name, string $categoryName) use ($merchant, $category) {
            $cat = $category($categoryName);
            if (!$cat) return;

            Brand::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'name' => $name,
                ],
                [
                    'id' => Str::uuid(),
                    'category_id' => $cat->id,
                ]
            );
        };

        /* ================= SOLAR PANELS ================= */

        foreach ([
                     'Longi',
                     'JA Solar',
                     'Jinko Solar',
                     'Canadian Solar',
                     'Trina Solar',
                 ] as $brand) {
            $create($brand, 'Solar Panels');
        }

        /* ================= INVERTERS ================= */

        foreach ([
                     'Huawei',
                     'Growatt',
                     'Inverex',
                     'GoodWe',
                     'Solis',
                 ] as $brand) {
            $create($brand, 'Inverters');
        }

        /* ================= BATTERIES ================= */

        foreach ([
                     'Phoenix',
                     'Exide',
                     'AGS',
                     'Narada',
                     'Pylontech',
                 ] as $brand) {
            $create($brand, 'Batteries');
        }

        /* ================= MONITORING ================= */

        foreach ([
                     'Huawei',
                     'Growatt',
                 ] as $brand) {
            $create($brand, 'Monitoring Devices');
        }

        /* ================= MOUNTING STRUCTURES ================= */

        foreach ([
                     'ZGN Fabrication',
                     'Local Fabricator',
                 ] as $brand) {
            $create($brand, 'Mounting Structures');
        }

        /* ================= ELECTRICAL & SAFETY ================= */

        foreach ([
                     'Schneider Electric',
                     'ABB',
                 ] as $brand) {
            $create($brand, 'Circuit Protection');
        }
    }
}
