<?php

namespace Database\Seeders;

use Database\Seeders\ZGN\Batteries\ZGNBatteryProductsOptionsSeeder;
use Database\Seeders\ZGN\Batteries\ZGNBatteryProductsOptionValuesSeeder;
use Database\Seeders\ZGN\Batteries\ZGNBatteryProductsSeeder;
use Database\Seeders\ZGN\Batteries\ZGNBatteryProductVariantsSeeder;
use Database\Seeders\ZGN\Batteries\ZGNBatteryProductVariantValuesSeeder;
use Database\Seeders\ZGN\DCSide\SolarCableSeeders;
use Database\Seeders\ZGN\SolarAMCServiceSeeder;
use Database\Seeders\ZGN\SolarBatteryAccessoriesSeeder;
use Database\Seeders\ZGN\SolarCommunicationModulesSeeder;
use Database\Seeders\ZGN\SolarEarthingSeeder;
use Database\Seeders\ZGN\SolarInstallationServiceSeeder;
use Database\Seeders\ZGN\SolarInverters\ZGNInverterProductsOptionsSeeder;
use Database\Seeders\ZGN\SolarInverters\ZGNInverterProductsOptionValuesSeeder;
use Database\Seeders\ZGN\SolarInverters\ZGNInverterProductsSeeder;
use Database\Seeders\ZGN\SolarInverters\ZGNInverterProductVariantsSeeder;
use Database\Seeders\ZGN\SolarInverters\ZGNInverterProductVariantValuesSeeder;
use Database\Seeders\ZGN\SolarMonitoringDevicesSeeder;
use Database\Seeders\ZGN\SolarNetMeteringServiceSeeder;
use Database\Seeders\ZGN\SolarPanels\ZGNSolarPanelProductsOptionsSeeder;
use Database\Seeders\ZGN\SolarPanels\ZGNSolarPanelProductsOptionValuesSeeder;
use Database\Seeders\ZGN\SolarPanels\ZGNSolarPanelProductsSeeder;
use Database\Seeders\ZGN\SolarPanels\ZGNSolarPanelProductVariantsSeeder;
use Database\Seeders\ZGN\SolarPanels\ZGNSolarPanelProductVariantValuesSeeder;
use Database\Seeders\ZGN\SolarProtectionSeeders;
use Database\Seeders\ZGN\SolarStructureSeeders;
use Database\Seeders\ZGN\ZGNBrandModelsSeeder;
use Database\Seeders\ZGN\ZGNBrandsSeeder;
use Database\Seeders\ZGN\ZGNSolarCategoriesSeeder;
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

            // ZGN Merchant Seeders
            ZGNSolarCategoriesSeeder::class,
            ZGNBrandsSeeder::class,
            ZGNBrandModelsSeeder::class,

            // Solar Panels
            ZGNSolarPanelProductsSeeder::class,
            ZGNSolarPanelProductsOptionsSeeder::class,
            ZGNSolarPanelProductsOptionValuesSeeder::class,
            ZGNSolarPanelProductVariantsSeeder::class,
            ZGNSolarPanelProductVariantValuesSeeder::class,

            // Inverters
            ZGNInverterProductsSeeder::class,
            ZGNInverterProductsOptionsSeeder::class,
            ZGNInverterProductsOptionValuesSeeder::class,
            ZGNInverterProductVariantsSeeder::class,
            ZGNInverterProductVariantValuesSeeder::class,

            // Battery
            ZGNBatteryProductsSeeder::class,
            ZGNBatteryProductsOptionsSeeder::class,
            ZGNBatteryProductsOptionValuesSeeder::class,
            ZGNBatteryProductVariantsSeeder::class,
            ZGNBatteryProductVariantValuesSeeder::class,

            // Rest of the seeders
            SolarCableSeeders::class,
            SolarProtectionSeeders::class,
            SolarStructureSeeders::class,
            SolarBatteryAccessoriesSeeder::class,
            SolarEarthingSeeder::class,
            SolarMonitoringDevicesSeeder::class,
            SolarCommunicationModulesSeeder::class,
            SolarInstallationServiceSeeder::class,
            SolarNetMeteringServiceSeeder::class,
            SolarAMCServiceSeeder::class,

            // Purchases, Sales, and Expenses (must be after products are created)
            PurchasesSeeder::class,
            SalesSeeder::class,
            ExpensesSeeder::class,
        ];

        $this->call($seeders);
    }
}
