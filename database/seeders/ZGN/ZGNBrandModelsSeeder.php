<?php

namespace Database\Seeders\ZGN;

use App\Models\Brand;
use App\Models\BrandModel;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNBrandModelsSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@zgngreenpvt.com')->first();
        if (!$merchant) return;

        /**
         * Helper: get brand by name
         */
        $brand = function (string $name) use ($merchant) {
            return Brand::where('merchant_id', $merchant->id)
                ->where('name', $name)
                ->first();
        };

        /**
         * Helper: create model
         */
        $create = function (string $brandName, array $models) use ($merchant, $brand) {
            $b = $brand($brandName);
            if (!$b) return;

            foreach ($models as $model) {
                BrandModel::firstOrCreate(
                    [
                        'merchant_id' => $merchant->id,
                        'brand_id' => $b->id,
                        'name' => $model,
                    ],
                    [
                        'id' => Str::uuid(),
                    ]
                );
            }
        };

        /* ================= SOLAR PANELS ================= */

        $create('Longi', [
            'LR5-72HPH-540M',
            'LR5-72HPH-550M',
            'LR5-72HPH-560M',
        ]);

        $create('JA Solar', [
            'JAM72S30-545/MR',
            'JAM72S30-550/MR',
        ]);

        $create('Jinko Solar', [
            'JKM550M-72HL4-V',
            'JKM555M-72HL4-V',
        ]);

        $create('Canadian Solar', [
            'CS6W-545MS',
            'CS6W-550MS',
        ]);

        $create('Trina Solar', [
            'TSM-DE18M(II)-545',
            'TSM-DE18M(II)-550',
        ]);

        /* ================= INVERTERS ================= */

        $create('Huawei', [
            'SUN2000-5K-MAP0',
            'SUN2000-10K-MAP0',
            'SUN2000-20K-MAP0',
        ]);

        $create('Growatt', [
            'MIN 5000TL-X',
            'MOD 10KTL3-X',
            'MID 20KTL3-X',
        ]);

        $create('Inverex', [
            'AXPERT-VM-III-5K',
            'Nitrox-10KW',
            'Nitrox-15KW',
        ]);

        $create('GoodWe', [
            'GW5000-ES',
            'GW10K-ET',
        ]);

        $create('Solis', [
            'S5-GR1P5K',
            'S5-GC20K',
        ]);

        /* ================= BATTERIES ================= */

        $create('Phoenix', [
            'TX-1800',
            'TX-2500',
        ]);

        $create('Exide', [
            'TR-2000',
            'TR-2500',
        ]);

        $create('AGS', [
            'SP-1800',
            'SP-2000',
        ]);

        $create('Narada', [
            'REXC-1000',
            'REXC-2000',
        ]);

        $create('Pylontech', [
            'US2000C',
            'US3000C',
        ]);

        /* ================= STRUCTURES ================= */

        $create('ZGN Fabrication', [
            'L1-ROOF-MOUNT',
            'L2-ELEVATED',
            'L3-HIGH-ELEVATION',
        ]);

        $create('Local Fabricator', [
            'CUSTOM-GI-FRAME',
            'CUSTOM-AL-FRAME',
        ]);
    }
}
