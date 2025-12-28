<?php

namespace Database\Seeders\ZGN\SolarPanels;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZGNSolarPanelProductsOptionValuesSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            'wattage' => ['540W', '550W', '560W'],
            'cell_type' => ['Monocrystalline', 'Bifacial'],
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
