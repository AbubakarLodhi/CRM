<?php

namespace Database\Seeders;

use Database\Seeders\ZGN\ZGNBrandModelsSeeder;
use Database\Seeders\ZGN\ZGNBrandsSeeder;
use Database\Seeders\ZGN\ZGNSolarCategoriesSeeder;
use Database\Seeders\ZGN\ZGNSolarPanelProductsOptionsSeeder;
use Database\Seeders\ZGN\ZGNSolarPanelProductsOptionValuesSeeder;
use Database\Seeders\ZGN\ZGNSolarPanelProductsSeeder;
use Database\Seeders\ZGN\ZGNSolarPanelProductVariantsSeeder;
use Database\Seeders\ZGN\ZGNSolarPanelProductVariantValuesSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $seeders = [
            PermissionsSeeder::class,
            RolesSeeder::class,
            CountriesSeeder::class,
            CitiesSeeder::class,
            AdminsSeeder::class,
            MerchantsSeeder::class,
            StaffsSeeder::class,
            BusinessesSeeder::class,
            BranchesSeeder::class,
            BranchUsersSeeder::class,
            CustomersSeeder::class,

            //ZGN Merchant Seeders
            ZGNSolarCategoriesSeeder::class,
            ZGNBrandsSeeder::class,
            ZGNBrandModelsSeeder::class,
            ZGNSolarPanelProductsSeeder::class,
            ZGNSolarPanelProductsOptionsSeeder::class,
            ZGNSolarPanelProductsOptionValuesSeeder::class,
            ZGNSolarPanelProductVariantsSeeder::class,
            ZGNSolarPanelProductVariantValuesSeeder::class

        ];

        $this->call($seeders);
    }
}
