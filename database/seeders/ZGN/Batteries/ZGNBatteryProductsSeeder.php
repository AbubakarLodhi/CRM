<?php

namespace Database\Seeders\ZGN\Batteries;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNBatteryProductsSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@zgngreenpvt.com')->first();
        if (!$merchant) return;

        $category = Category::where('merchant_id', $merchant->id)->where('name', 'Batteries')->first();

        $merchantSlug = collect(explode(' ', $merchant->name))
            ->map(fn($word) => Str::lower(Str::substr($word, 0, 1)))
            ->implode('');

        $sku = "{$merchantSlug}-solar-battery";

        Product::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'sku' => $sku,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Solar Battery',
                'description' => 'Energy storage battery for solar systems',
                'category_id' => $category?->parent_id,
                'sub_category_id' => $category?->id,
                'type' => 'stock',
                'unit' => 'pcs',
                'track_inventory' => true,
            ]
        );
    }
}
