<?php

namespace Database\Seeders\ZGN\SolarInverters;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNInverterProductsSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@zgngreenpvt.com')->first();
        if (!$merchant) return;

        $category = Category::where('merchant_id', $merchant->id)->where('name', 'Inverters')->first();

        $merchantSlug = collect(explode(' ', $merchant->name))
            ->map(fn($word) => Str::lower(Str::substr($word, 0, 1)))
            ->implode('');

        $sku = "{$merchantSlug}-solar-inverter";

        Product::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'sku' => $sku,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Solar Inverter',
                'description' => 'Solar power inverter',
                'category_id' => $category?->parent_id,
                'sub_category_id' => $category?->id,
                'type' => 'stock',
                'unit' => 'pcs',
                'track_inventory' => true,
            ]
        );
    }
}
