<?php

namespace Database\Seeders\ZGN\SolarInverters;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNInverterProductVariantValuesSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'HUA-5K-HYB' => [
                'capacity'  => '5kW',
                'phase'     => 'Single Phase',
                'grid_type' => 'Hybrid',
            ],
            'HUA-10K-HYB' => [
                'capacity'  => '10kW',
                'phase'     => 'Three Phase',
                'grid_type' => 'Hybrid',
            ],
            'GRT-5K-ONG' => [
                'capacity'  => '5kW',
                'phase'     => 'Single Phase',
                'grid_type' => 'On-Grid',
            ],
            'GRT-20K-ONG' => [
                'capacity'  => '20kW',
                'phase'     => 'Three Phase',
                'grid_type' => 'On-Grid',
            ],
            'INV-5K-HYB' => [
                'capacity'  => '5kW',
                'phase'     => 'Single Phase',
                'grid_type' => 'Hybrid',
            ],
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
