<?php

namespace Database\Seeders;

use Database\Seeders\Halaynoor\HalaynoorBrandModelsSeeder;
use Database\Seeders\Halaynoor\HalaynoorBrandsSeeder;
use Database\Seeders\Halaynoor\HalaynoorCategoriesSeeder;
use Database\Seeders\Halaynoor\HalaynoorProductsSeeder;
use Database\Seeders\Halaynoor\HalaynoorProductVariantsSeeder;
use Database\Seeders\ZGN\Batteries\ZGNBatteryProductsOptionsSeeder;
use Database\Seeders\ZGN\Batteries\ZGNBatteryProductsOptionValuesSeeder;
use Database\Seeders\ZGN\Batteries\ZGNBatteryProductsSeeder;
use Database\Seeders\ZGN\Batteries\ZGNBatteryProductVariantsSeeder;
use Database\Seeders\ZGN\Batteries\ZGNBatteryProductVariantValuesSeeder;
use Database\Seeders\ZGN\DCSide\SolarCableSeeders;
use Database\Seeders\ZGN\EveeElectricBikes\EveeElectricBikesBrandModelsSeeder;
use Database\Seeders\ZGN\EveeElectricBikes\EveeElectricBikesBrandsSeeder;
use Database\Seeders\ZGN\EveeElectricBikes\EveeElectricBikesCategoriesSeeder;
use Database\Seeders\ZGN\EveeElectricBikes\EveeElectricBikesProductsSeeder;
use Database\Seeders\ZGN\EveeElectricBikes\EveeElectricBikesProductVariantsSeeder;
use Database\Seeders\ZGN\PremiumLubricantsOils\PremiumLubricantsOilsBrandModelsSeeder;
use Database\Seeders\ZGN\PremiumLubricantsOils\PremiumLubricantsOilsBrandsSeeder;
use Database\Seeders\ZGN\PremiumLubricantsOils\PremiumLubricantsOilsCategoriesSeeder;
use Database\Seeders\ZGN\PremiumLubricantsOils\PremiumLubricantsOilsProductsSeeder;
use Database\Seeders\ZGN\PremiumLubricantsOils\PremiumLubricantsOilsProductVariantsSeeder;
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
use Database\Seeders\ZGN\TyresAlloyWheels\TyresAlloyWheelsBrandModelsSeeder;
use Database\Seeders\ZGN\TyresAlloyWheels\TyresAlloyWheelsBrandsSeeder;
use Database\Seeders\ZGN\TyresAlloyWheels\TyresAlloyWheelsCategoriesSeeder;
use Database\Seeders\ZGN\TyresAlloyWheels\TyresAlloyWheelsProductsSeeder;
use Database\Seeders\ZGN\TyresAlloyWheels\TyresAlloyWheelsProductVariantsSeeder;
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
            MerchantsSeeder::class,
            StaffsSeeder::class,
            BusinessesSeeder::class,
            BranchesSeeder::class,
            BranchUsersSeeder::class,
            CustomersSeeder::class,
            CashFlowsSeeder::class,
            PermissionsModulesSeeder::class,
            MerchantPermissionModulesSeeder::class,

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

            // Evee Electric Bikes Seeders
            EveeElectricBikesCategoriesSeeder::class,
            EveeElectricBikesBrandsSeeder::class,
            EveeElectricBikesBrandModelsSeeder::class,
            EveeElectricBikesProductsSeeder::class,
            EveeElectricBikesProductVariantsSeeder::class,

            // Tyres & Alloy Wheels Seeders
            TyresAlloyWheelsCategoriesSeeder::class,
            TyresAlloyWheelsBrandsSeeder::class,
            TyresAlloyWheelsBrandModelsSeeder::class,
            TyresAlloyWheelsProductsSeeder::class,
            TyresAlloyWheelsProductVariantsSeeder::class,

            // Premium Lubricants & Oils Seeders
            PremiumLubricantsOilsCategoriesSeeder::class,
            PremiumLubricantsOilsBrandsSeeder::class,
            PremiumLubricantsOilsBrandModelsSeeder::class,
            PremiumLubricantsOilsProductsSeeder::class,
            PremiumLubricantsOilsProductVariantsSeeder::class,

            // Halaynoor Merchant Seeders
            HalaynoorCategoriesSeeder::class,
            HalaynoorBrandsSeeder::class,
            HalaynoorBrandModelsSeeder::class,
            HalaynoorProductsSeeder::class,
            HalaynoorProductVariantsSeeder::class,

            // Purchases, Sales, and Expenses (must be after products are created)
            PurchasesSeeder::class,
            SalesSeeder::class,
            ExpensesSeeder::class,

            // Payrolls (must be after staff/users are created)
            PayrollsSeeder::class,
        ];

        $this->call($seeders);
    }
}
