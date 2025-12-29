<?php

namespace Database\Seeders\ZGN\Batteries;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNBatteryProductsOptionValuesSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            'chemistry' => ['Lead Acid', 'LiFePO4'],
            'voltage'   => ['12V', '48V'],
            'capacity'  => ['150Ah', '200Ah', '2.4kWh', '3.5kWh'],
        ];

        foreach ($values as $optionName => $vals) {
            $option = ProductOption::where('name', $optionName)->first();
            if (!$option) continue;

            foreach ($vals as $value) {
                ProductOptionValue::firstOrCreate(
                    [
                        'product_option_id' => $option->id,
                        'value' => $value,
                    ],
                    [
                        'id' => Str::uuid(),
                    ]
                );
            }
        }
    }
}
