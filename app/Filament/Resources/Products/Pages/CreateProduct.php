<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Branch;
use App\Models\Product;
use App\Support\ProductUnit;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['unit'] = ProductUnit::normalize($data['unit'] ?? null);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            $merchantId = $data['merchant_id'];

            $sku = $this->generateSku($merchantId, $data['name']);

            return Product::create([
                'merchant_id'        => $merchantId,
                'name'               => $data['name'],
                'sku'                => $sku,
                'description'        => $data['description'] ?? null,
                'type'               => $data['type'],
                'unit'               => ProductUnit::normalize($data['unit'] ?? null),
                'track_inventory'    => (bool) ($data['track_inventory'] ?? true),
                'is_variable_price'  => (bool) ($data['is_variable_price'] ?? false),
                'purchase_price'     => $data['purchase_price'] ?? null,
                'selling_price'      => $data['selling_price'] ?? null,
                'is_active'          => (bool) ($data['is_active'] ?? true),
                'category_id'        => $data['category_id'] ?? null,
                'sub_category_id'    => $data['sub_category_id'] ?? null,
                'brand_id'           => $data['brand_id'] ?? null,
                'brand_model_id'     => $data['brand_model_id'] ?? null,
            ]);
        });
    }

    protected function afterCreate(): void
    {
        $state   = $this->form->getRawState();
        $product = $this->record;

        /* -------------------------
         | Sync Branches
         |--------------------------*/
        if (! empty($state['branches'])) {
            $branchSync = [];

            foreach ($state['branches'] as $branchId) {
                $branchSync[$branchId] = ['id' => (string) Str::uuid()];
            }

            $product->branches()->sync($branchSync);

            /* -------------------------
             | Derive Businesses from Branches
             |--------------------------*/
            $businessIds = Branch::whereIn('id', $state['branches'])
                ->pluck('business_id')
                ->unique()
                ->values();

            $businessSync = [];

            foreach ($businessIds as $businessId) {
                $businessSync[$businessId] = ['id' => (string) Str::uuid()];
            }

            $product->businesses()->sync($businessSync);
        }

        /* -------------------------
         | Product Image
         |--------------------------*/
        $path = collect($state['product_image'] ?? null)->first();

        if ($path) {
            $product->productImage()?->delete();

            $product->productImage()->create([
                'merchant_id' => $product->merchant_id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::PRODUCT_IMAGE,
                'photo_url'   => $path,
            ]);
        }

        Notification::make()
            ->title('Product created')
            ->success()
            ->send();
    }

    private function generateSku(string $merchantId, string $productName): string
    {
        $productCode = $this->initials($productName);

        do {
            $random = random_int(1000, 9999);
            $sku = "{$productCode}-{$random}";
        } while (
            Product::where('merchant_id', $merchantId)
                ->where('sku', $sku)
                ->exists()
        );

        return $sku;
    }

    private function initials(string $value): string
    {
        return collect(preg_split('/\s+/', trim($value)) ?: [])
            ->map(fn ($w) => strtoupper(Str::substr($w, 0, 1)))
            ->implode('');
    }
}
