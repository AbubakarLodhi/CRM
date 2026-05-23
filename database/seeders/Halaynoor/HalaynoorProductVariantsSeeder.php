<?php

namespace Database\Seeders\Halaynoor;

use App\Models\Merchant;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HalaynoorProductVariantsSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@halaynoor.com')->first();
        if (! $merchant) {
            return;
        }

        $products = Product::where('merchant_id', $merchant->id)->get();

        if ($products->isEmpty()) {
            return;
        }

        $merchantSlug = 'halaynoor';

        foreach ($products as $product) {
            $variantSku = strtoupper($merchantSlug).'-VAR-'.strtoupper(substr(uniqid(), -8));

            ProductVariant::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'sku' => $variantSku,
                ],
                [
                    'id' => Str::uuid(),
                    'product_id' => $product->id,
                    'name' => $product->name.' - Standard',
                    'purchase_price' => $product->purchase_price,
                    'selling_price' => $product->selling_price,
                    'is_active' => true,
                ]
            );
        }
    }
}
