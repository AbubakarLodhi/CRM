<?php

namespace App\Support;

use App\Models\City;
use App\Models\Country;
use Illuminate\Support\Str;

class GeoFormFields
{
    public static function defaultCountryId(): ?string
    {
        return Country::query()
            ->where('code', 'PK')
            ->value('id')
            ?? Country::query()
                ->orderBy('name')
                ->value('id');
    }

    /**
     * @return array<string, string>
     */
    public static function countryOptions(): array
    {
        return Country::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    public static function defaultCountryIds(): array
    {
        $id = self::defaultCountryId();

        return $id ? [$id] : [];
    }

    public static function createCity(array $data): string
    {
        return City::query()
            ->firstOrCreate(
                [
                    'country_id' => $data['country_id'],
                    'name' => trim((string) $data['name']),
                ],
                [
                    'id' => (string) Str::uuid(),
                ]
            )
            ->getKey();
    }
}
