<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        $citiesByCountry = [

            // 🇵🇰 PAKISTAN — extensive list
            'PK' => [
                'Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan',
                'Peshawar', 'Quetta', 'Hyderabad', 'Sialkot', 'Gujranwala', 'Sargodha',
                'Bahawalpur', 'Sukkur', 'Larkana', 'Sheikhupura', 'Jhelum', 'Abbottabad',
                'Mardan', 'Swat', 'Chaman', 'Gwadar', 'Turbat', 'Khuzdar', 'Dera Ghazi Khan',
                'Rahim Yar Khan', 'Okara', 'Kasur', 'Vehari', 'Khanewal', 'Mingora',
                'Mansehra', 'Nowshera', 'Charsadda', 'Haripur', 'Attock', 'Taxila',
                'Hafizabad', 'Jhang', 'Toba Tek Singh', 'Pakpattan','Chunian',
            ],

            // 🇺🇸 USA
            'US' => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Dallas', 'San Francisco', 'Seattle'],

            // 🇬🇧 UK
            'GB' => ['London', 'Manchester', 'Birmingham', 'Liverpool', 'Leeds', 'Bristol'],

            // 🇨🇦 Canada
            'CA' => ['Toronto', 'Vancouver', 'Montreal', 'Calgary', 'Ottawa'],

            // 🇦🇺 Australia
            'AU' => ['Sydney', 'Melbourne', 'Brisbane', 'Perth', 'Adelaide'],

            // 🇦🇪 UAE
            'AE' => ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah'],

            // 🇸🇦 Saudi Arabia
            'SA' => ['Riyadh', 'Jeddah', 'Dammam', 'Makkah', 'Madinah'],

            // 🇮🇳 India
            'IN' => ['Delhi', 'Mumbai', 'Bangalore', 'Chennai', 'Hyderabad', 'Pune'],

            // 🇨🇳 China
            'CN' => ['Beijing', 'Shanghai', 'Guangzhou', 'Shenzhen'],

            // 🇩🇪 Germany
            'DE' => ['Berlin', 'Munich', 'Hamburg', 'Frankfurt'],

            // 🇫🇷 France
            'FR' => ['Paris', 'Lyon', 'Marseille', 'Nice'],

            // 🇯🇵 Japan
            'JP' => ['Tokyo', 'Osaka', 'Kyoto', 'Yokohama'],

            // 🇸🇬 Singapore
            'SG' => ['Singapore'],

            // 🇹🇷 Turkey
            'TR' => ['Istanbul', 'Ankara', 'Izmir'],

            // 🇶🇦 Qatar
            'QA' => ['Doha'],

            // 🇰🇼 Kuwait
            'KW' => ['Kuwait City'],

            // 🇧🇭 Bahrain
            'BH' => ['Manama'],

            // 🇴🇲 Oman
            'OM' => ['Muscat', 'Salalah'],

            // 🇪🇬 Egypt
            'EG' => ['Cairo', 'Alexandria', 'Giza'],

            // 🇿🇦 South Africa
            'ZA' => ['Johannesburg', 'Cape Town', 'Durban', 'Pretoria'],
        ];

        foreach ($citiesByCountry as $countryCode => $cities) {
            $country = Country::where('code', $countryCode)->first();
            if (!$country) continue;

            foreach ($cities as $city) {
                City::firstOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $city,
                    ],
                    [
                        'id' => Str::uuid(),
                    ]
                );
            }
        }
    }
}
