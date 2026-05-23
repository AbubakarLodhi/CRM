<?php

namespace Database\Seeders\Halaynoor;

use App\Models\Brand;
use App\Models\BrandModel;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HalaynoorBrandModelsSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@halaynoor.com')->first();
        if (! $merchant) {
            return;
        }

        $brand = Brand::where('merchant_id', $merchant->id)
            ->where('name', 'Halaynoor')
            ->first();

        if (! $brand) {
            return;
        }

        $models = [
            'Classic Collection',
            'Premium Collection',
            'Bridal Collection',
            'Traditional Collection',
            'Modern Collection',
            'Luxury Collection',
            'Signature Collection',
            'Heritage Collection',
        ];

        foreach ($models as $modelName) {
            BrandModel::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'brand_id' => $brand->id,
                    'name' => $modelName,
                ],
                [
                    'id' => Str::uuid(),
                ]
            );
        }
    }
}
