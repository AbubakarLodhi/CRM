<?php

namespace Database\Seeders\ZGN;

use App\Models\Business;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNSolarPanelProductsSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@zgngreenpvt.com')->first();
        if (!$merchant) return;

        $business = Business::where('merchant_id', $merchant->id)->where('name', 'Solar Systems')->first();
        if (!$business) return;

        $category = Category::where('merchant_id', $merchant->id)->where('name', 'Solar Panels')->first();

        $merchantSlug = collect(explode(' ', $merchant->name))
            ->map(fn($word) => Str::lower(Str::substr($word, 0, 1)))
            ->implode('');

        $businessSlug = collect(explode(' ', $business->name))
            ->map(fn($word) => Str::lower(Str::substr($word, 0, 1)))
            ->implode('');

        $sku = "{$merchantSlug}-{$businessSlug}-solar-panel";

        Product::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'sku' => $sku,
            ],
            [
                'id' => Str::uuid(),
                'business_id' => $business->id,
                'name' => 'Solar Panel',
                'description' => 'Photovoltaic solar panel',
                'category_id' => $category?->parent_id,
                'sub_category_id' => $category?->id,
                'type' => 'stock',
                'unit' => 'pcs',
                'track_inventory' => true,
            ]
        );
    }
}
