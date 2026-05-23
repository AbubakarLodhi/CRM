<?php

namespace Database\Seeders\Halaynoor;

use App\Models\Category;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HalaynoorCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $merchant = Merchant::where('email', 'info@halaynoor.com')->first();
        if (! $merchant) {
            return;
        }

        $create = function (string $name, ?string $parentId = null) use ($merchant) {
            return Category::firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'parent_id' => $parentId,
                    'name' => $name,
                ],
                [
                    'id' => Str::uuid(),
                ]
            );
        };

        $jewellery = $create('Jewellery');

        $create('Earrings', $jewellery->id);
        $create('Rings', $jewellery->id);
        $create('Bangles', $jewellery->id);
        $create('Pendants', $jewellery->id);
        $create('Necklaces', $jewellery->id);
        $create('Bracelets', $jewellery->id);
        $create('Bridal Sets', $jewellery->id);
        $create('Anklets', $jewellery->id);
        $create('Headpieces', $jewellery->id);
    }
}
