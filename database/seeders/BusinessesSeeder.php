<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\City;
use App\Models\Country;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BusinessesSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $zgn = Merchant::where('email', 'info@zgngreenpvt.com')->first();
        $halaynoor = Merchant::where('email', 'info@halaynoor.com')->first();

        $pakistan = Country::where('code', 'PK')->first();
        $karachi = City::where('name', 'Karachi')->first();
        if (!$pakistan || !$karachi) return;

        if ($zgn) {
            $this->createBusiness(
                $zgn->id,
                $pakistan->id,
                $karachi->id,
                [
                    'Solar Systems',
                    'Evee Electric Bikes',
                    'Tyres & Alloy Wheels',
                    'Premium Lubricants & Oils',
                ]
            );
        }

        if ($halaynoor) {
            $this->createBusiness(
                $halaynoor->id,
                $pakistan->id,
                $karachi->id,
                [
                    'Halaynoor'
                ]
            );
        }
    }

    /**
     * @param string $merchantId
     * @param string $countryId
     * @param string $cityId
     * @param array $businesses
     * @return void
     */
    private function createBusiness(
        string $merchantId,
        string $countryId,
        string $cityId,
        array  $businesses = []
    ): void
    {
        foreach ($businesses as $name) {
            Business::firstOrCreate(
                ['merchant_id' => $merchantId, 'name' => $name],
                [
                    'id' => Str::uuid(),
                    'description' => $name . ' business',
                    'status' => true,
//                    'country_id' => $countryId,
//                    'city_id' => $cityId,
                    'postal_code' => '75500',
                ]
            );
        }
    }
}
