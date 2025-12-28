<?php

namespace Database\Seeders\ZGN\SolarInverters;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNInverterProductsOptionValuesSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            'capacity'  => ['5kW', '10kW', '20kW'],
            'phase'     => ['Single Phase', 'Three Phase'],
            'grid_type' => ['On-Grid', 'Hybrid'],
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
