<?php

namespace Database\Seeders\ZGN\SolarPanels;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNSolarPanelProductVariantValuesSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'LNG-540-MONO' => ['wattage' => '540W', 'cell_type' => 'Monocrystalline'],
            'LNG-550-MONO' => ['wattage' => '550W', 'cell_type' => 'Monocrystalline'],
            'JA-550-MONO'  => ['wattage' => '550W', 'cell_type' => 'Monocrystalline'],
            'JNK-560-BIF'  => ['wattage' => '560W', 'cell_type' => 'Bifacial'],
        ];

        foreach ($map as $sku => $values) {
            $variant = ProductVariant::where('sku', $sku)->first();
            if (!$variant) continue;

            foreach ($values as $optionName => $valueName) {
                $option = ProductOption::where('name', $optionName)->first();
                $value = ProductOptionValue::where('product_option_id', $option->id)
                    ->where('value', $valueName)
                    ->first();

                ProductVariantValue::firstOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'product_option_id' => $option->id,
                    ],
                    [
                        'id' => Str::uuid(),
                        'product_option_value_id' => $value->id,
                    ]
                );
            }
        }
    }
}
