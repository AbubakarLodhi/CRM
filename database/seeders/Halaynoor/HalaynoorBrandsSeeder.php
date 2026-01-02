<?php

namespace Database\Seeders\Halaynoor;

use App\Models\Brand;
use App\Models\BrandCategory;
use App\Models\Category;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HalaynoorBrandsSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@halaynoor.com')->first();
        if (! $merchant) {
            return;
        }

        $jewelleryCategory = Category::where('merchant_id', $merchant->id)
            ->where('name', 'Jewellery')
            ->whereNull('parent_id')
            ->first();

        if (! $jewelleryCategory) {
            return;
        }

        $brand = Brand::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'name' => 'Halaynoor',
            ],
            [
                'id' => Str::uuid(),
            ]
        );

        BrandCategory::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'brand_id' => $brand->id,
                'category_id' => $jewelleryCategory->id,
            ],
            [
                'id' => Str::uuid(),
            ]
        );
    }
}
