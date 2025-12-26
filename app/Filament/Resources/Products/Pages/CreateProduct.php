<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Business;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            // 1) Create Product first
            $merchantId = $data['merchant_id'];

            $business = Business::findOrFail($data['business_id']);

            $sku = $this->generateSku($merchantId, $business->name, $data['name']);

            $product = Product::create([
                'merchant_id'       => $merchantId,
                'business_id'       => $data['business_id'],
                'name'              => $data['name'],
                'sku'               => $sku,
                'description'       => $data['description'] ?? null,
                'type'              => $data['type'],
                'unit'              => $data['unit'],
                'track_inventory'   => (bool)($data['track_inventory'] ?? true),
                'is_variable_price' => (bool)($data['is_variable_price'] ?? false),
                'purchase_price'    => $data['purchase_price'] ?? null,
                'selling_price'     => $data['selling_price'] ?? null,
                'is_active'         => (bool)($data['is_active'] ?? true),

                'category_id'       => $data['category_id'] ?? null,
                'sub_category_id'   => $data['sub_category_id'] ?? null,
                'brand_id'          => $data['brand_id'] ?? null,
                'brand_model_id'    => $data['brand_model_id'] ?? null,
            ]);

            // 2) Create Options + Values
            // We'll build a map: option_key -> (value_string -> value_id)
            $valueIdMap = [];

            foreach (($data['options'] ?? []) as $opt) {
                $option = $product->options()->create([
                    'name'         => $opt['name'],
                    'display_name' => $opt['display_name'] ?? null,
                ]);

                $valueIdMap[$option->id] = [];

                foreach (($opt['values'] ?? []) as $val) {
                    $v = $option->values()->create([
                        'value' => $val['value'],
                    ]);

                    $valueIdMap[$option->id][$v->value] = $v->id;
                }
            }

            // 3) Create Variants
            foreach (($data['variants'] ?? []) as $variantData) {
                $variant = $product->variants()->create([
                    'merchant_id'    => $merchantId,
                    'name'           => $variantData['name'] ?? null,
                    'sku'            => $variantData['sku'] ?? null,
                    'purchase_price' => $variantData['purchase_price'] ?? null,
                    'selling_price'  => $variantData['selling_price'] ?? null,
                    'is_active'      => (bool)($variantData['is_active'] ?? true),
                ]);

                // 4) Create Variant Values (selections)
                // We need to resolve option_key + value_string to the correct IDs
                $options = $product->options()->get()->keyBy('name');

                foreach (($variantData['selections'] ?? []) as $sel) {
                    $optionKey = $sel['option_key'] ?? null;
                    $valueStr  = $sel['value'] ?? null;

                    if (! $optionKey || ! $valueStr) {
                        continue;
                    }

                    $option = $options->get($optionKey);
                    if (! $option) {
                        continue;
                    }

                    $valueId = $valueIdMap[$option->id][$valueStr] ?? null;
                    if (! $valueId) {
                        continue;
                    }

                    $variant->values()->create([
                        'product_option_id'       => $option->id,
                        'product_option_value_id' => $valueId,
                    ]);
                }
            }

            return $product;
        });
    }

    private function generateSku(string $merchantId, string $businessName, string $productName): string
    {
        $businessCode = $this->initials($businessName); // Halay Noor -> HN
        $productCode  = $this->initials($productName);  // Necklace -> N (or NE if you want 2 letters)

        do {
            $random = random_int(1000, 9999);
            $sku = "{$businessCode}-{$productCode}-{$random}";
        } while (
            Product::where('merchant_id', $merchantId)->where('sku', $sku)->exists()
        );

        return $sku;
    }

    private function initials(string $value): string
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        return collect($parts)
            ->filter()
            ->map(fn ($w) => strtoupper(Str::substr($w, 0, 1)))
            ->implode('');
    }
}
