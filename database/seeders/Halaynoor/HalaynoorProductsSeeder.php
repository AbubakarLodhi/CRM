<?php

namespace Database\Seeders\Halaynoor;

use App\Models\Brand;
use App\Models\BrandModel;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HalaynoorProductsSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@halaynoor.com')->first();
        if (! $merchant) {
            return;
        }

        $jewelleryCategory = Category::where('merchant_id', $merchant->id)
            ->where('name', 'Jewellery')
            ->whereNull('parent_id')
            ->first();

        if (! $jewelleryCategory) {
            return;
        }

        $brand = Brand::where('merchant_id', $merchant->id)
            ->where('name', 'Halaynoor')
            ->first();

        if (! $brand) {
            return;
        }

        $brandModels = BrandModel::where('merchant_id', $merchant->id)
            ->where('brand_id', $brand->id)
            ->get();

        if ($brandModels->isEmpty()) {
            return;
        }

        $subCategories = [
            'Earrings' => [
                ['name' => 'Gold Stud Earrings', 'description' => 'Elegant gold stud earrings with intricate designs'],
                ['name' => 'Diamond Drop Earrings', 'description' => 'Stunning diamond drop earrings for special occasions'],
                ['name' => 'Pearl Earrings', 'description' => 'Classic pearl earrings with modern twist'],
                ['name' => 'Chandelier Earrings', 'description' => 'Elaborate chandelier earrings for bridal wear'],
            ],
            'Rings' => [
                ['name' => 'Gold Wedding Ring', 'description' => 'Traditional gold wedding ring with engraved patterns'],
                ['name' => 'Diamond Engagement Ring', 'description' => 'Beautiful diamond engagement ring in various cuts'],
                ['name' => 'Cocktail Ring', 'description' => 'Statement cocktail ring with gemstones'],
                ['name' => 'Stackable Rings', 'description' => 'Modern stackable rings set'],
            ],
            'Bangles' => [
                ['name' => 'Gold Bangles Set', 'description' => 'Traditional gold bangles set with intricate work'],
                ['name' => 'Kada Bangles', 'description' => 'Heavy kada bangles with traditional designs'],
                ['name' => 'Diamond Bangles', 'description' => 'Luxurious diamond-studded bangles'],
                ['name' => 'Charm Bangles', 'description' => 'Elegant charm bangles with modern appeal'],
            ],
            'Pendants' => [
                ['name' => 'Gold Pendant', 'description' => 'Classic gold pendant with traditional motifs'],
                ['name' => 'Diamond Pendant', 'description' => 'Exquisite diamond pendant in various designs'],
                ['name' => 'Gemstone Pendant', 'description' => 'Beautiful gemstone pendants with gold setting'],
                ['name' => 'Locket Pendant', 'description' => 'Vintage-style locket pendant'],
            ],
            'Necklaces' => [
                ['name' => 'Gold Necklace Set', 'description' => 'Complete gold necklace set with matching earrings'],
                ['name' => 'Diamond Necklace', 'description' => 'Luxurious diamond necklace for special occasions'],
                ['name' => 'Pearl Necklace', 'description' => 'Elegant pearl necklace with gold accents'],
                ['name' => 'Choker Necklace', 'description' => 'Modern choker necklace with contemporary design'],
            ],
            'Bracelets' => [
                ['name' => 'Gold Bracelet', 'description' => 'Classic gold bracelet with link design'],
                ['name' => 'Diamond Tennis Bracelet', 'description' => 'Elegant diamond tennis bracelet'],
                ['name' => 'Charm Bracelet', 'description' => 'Charming bracelet with multiple pendants'],
                ['name' => 'Cuff Bracelet', 'description' => 'Bold cuff bracelet with intricate patterns'],
            ],
            'Bridal Sets' => [
                ['name' => 'Complete Bridal Set', 'description' => 'Complete bridal jewelry set including necklace, earrings, and bangles'],
                ['name' => 'Heavy Bridal Set', 'description' => 'Heavy traditional bridal set with elaborate designs'],
                ['name' => 'Modern Bridal Set', 'description' => 'Contemporary bridal set with modern aesthetics'],
                ['name' => 'Diamond Bridal Set', 'description' => 'Luxurious diamond bridal set for special occasions'],
            ],
            'Anklets' => [
                ['name' => 'Gold Anklet', 'description' => 'Traditional gold anklet with bells'],
                ['name' => 'Diamond Anklet', 'description' => 'Elegant diamond-studded anklet'],
                ['name' => 'Charm Anklet', 'description' => 'Delicate charm anklet with small pendants'],
            ],
            'Headpieces' => [
                ['name' => 'Bridal Maang Tikka', 'description' => 'Elaborate bridal maang tikka with diamonds'],
                ['name' => 'Jhoomar', 'description' => 'Traditional jhoomar for bridal wear'],
                ['name' => 'Hair Accessories Set', 'description' => 'Complete hair accessories set for brides'],
            ],
        ];

        $merchantSlug = 'halaynoor';

        foreach ($subCategories as $subCategoryName => $products) {
            $subCategory = Category::where('merchant_id', $merchant->id)
                ->where('name', $subCategoryName)
                ->where('parent_id', $jewelleryCategory->id)
                ->first();

            if (! $subCategory) {
                continue;
            }

            foreach ($products as $productData) {
                $brandModel = $brandModels->random();
                $sku = strtoupper($merchantSlug).'-'.strtoupper(substr($subCategoryName, 0, 3)).'-'.strtoupper(substr(uniqid(), -6));

                Product::firstOrCreate(
                    [
                        'merchant_id' => $merchant->id,
                        'sku' => $sku,
                    ],
                    [
                        'id' => Str::uuid(),
                        'category_id' => $jewelleryCategory->id,
                        'sub_category_id' => $subCategory->id,
                        'brand_id' => $brand->id,
                        'brand_model_id' => $brandModel->id,
                        'name' => $productData['name'],
                        'description' => $productData['description'],
                        'purchase_price' => rand(5000, 50000) / 100,
                        'selling_price' => rand(8000, 80000) / 100,
                        'type' => 'stock',
                        'unit' => 'pcs',
                        'track_inventory' => true,
                        'is_variable_price' => false,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
