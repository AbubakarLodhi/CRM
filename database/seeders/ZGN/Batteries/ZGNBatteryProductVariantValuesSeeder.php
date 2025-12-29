<?php

namespace Database\Seeders\ZGN\Batteries;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNBatteryProductVariantValuesSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'PHX-150AH-LA' => [
                'chemistry' => 'Lead Acid',
                'voltage'   => '12V',
                'capacity'  => '150Ah',
            ],
            'PHX-200AH-LA' => [
                'chemistry' => 'Lead Acid',
                'voltage'   => '12V',
                'capacity'  => '200Ah',
            ],
            'AGS-200AH-LA' => [
                'chemistry' => 'Lead Acid',
                'voltage'   => '12V',
                'capacity'  => '200Ah',
            ],
            'PYL-2.4KWH-LFP' => [
                'chemistry' => 'LiFePO4',
                'voltage'   => '48V',
                'capacity'  => '2.4kWh',
            ],
            'PYL-3.5KWH-LFP' => [
                'chemistry' => 'LiFePO4',
                'voltage'   => '48V',
                'capacity'  => '3.5kWh',
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
