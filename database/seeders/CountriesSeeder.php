<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Pakistan', 'code' => 'PK'],
            ['name' => 'United States', 'code' => 'US'],
            ['name' => 'United Kingdom', 'code' => 'GB'],
            ['name' => 'Canada', 'code' => 'CA'],
            ['name' => 'Australia', 'code' => 'AU'],
            ['name' => 'United Arab Emirates', 'code' => 'AE'],
            ['name' => 'Saudi Arabia', 'code' => 'SA'],
            ['name' => 'India', 'code' => 'IN'],
            ['name' => 'China', 'code' => 'CN'],
            ['name' => 'Germany', 'code' => 'DE'],
            ['name' => 'France', 'code' => 'FR'],
            ['name' => 'Italy', 'code' => 'IT'],
            ['name' => 'Spain', 'code' => 'ES'],
            ['name' => 'Netherlands', 'code' => 'NL'],
            ['name' => 'Singapore', 'code' => 'SG'],
            ['name' => 'Malaysia', 'code' => 'MY'],
            ['name' => 'Indonesia', 'code' => 'ID'],
            ['name' => 'Japan', 'code' => 'JP'],
            ['name' => 'South Korea', 'code' => 'KR'],
            ['name' => 'Turkey', 'code' => 'TR'],
            ['name' => 'Qatar', 'code' => 'QA'],
            ['name' => 'Kuwait', 'code' => 'KW'],
            ['name' => 'Bahrain', 'code' => 'BH'],
            ['name' => 'Oman', 'code' => 'OM'],
            ['name' => 'Egypt', 'code' => 'EG'],
            ['name' => 'South Africa', 'code' => 'ZA'],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(
                ['code' => $country['code']],
                [
                    'id' => Str::uuid(),
                    'name' => $country['name'],
                ]
            );
        }
    }
}
