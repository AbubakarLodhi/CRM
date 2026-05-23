<?php
namespace App\Filament\Resources\Brands\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Brands\BrandsResource;
use App\Models\Brand;
use App\Models\BrandCategory;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateBrands extends CreateRecord
{
    protected static string $resource = BrandsResource::class;

    protected array $categoryIds = [];
    public int $brandLogoInputKey = 0;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function preserveFormDataWhenCreatingAnother(array $data): array
    {
        return [
            'brand_logo' => null,
        ];
    }

    public function createAnother(): void
    {
        parent::createAnother();
        $this->brandLogoInputKey++;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        return $data;
    }

    protected function beforeCreate(): void
    {
        $state = $this->form->getRawState();

        $merchantId = $state['merchant_id'] ?? null;
        $brandName  = trim($state['name'] ?? '');

        if (! $merchantId || $brandName === '') {
            return;
        }

        // find existing brand
        $existingBrand = Brand::where('merchant_id', $merchantId)
            ->whereRaw('LOWER(name) = ?', [strtolower($brandName)])
            ->first();

        if ($existingBrand) {
            // reuse existing brand
            $this->record = $existingBrand;

            // ✅ IMPORTANT: attach categories HERE (because afterCreate won't run after halt)
            $this->attachCategoriesToBrand($this->record->id, $merchantId);

            // ✅ logo (optional): only create logo if user uploaded one
            $this->handleLogoUpload();

            Notification::make()
                ->title('Brand already exists')
                ->body('Existing brand was reused and assigned to the selected categories.')
                ->success()
                ->send();

            $this->form->fill([
                'name'         => null,
                'category_ids' => [],
                'brand_logo'   => null,
            ]);

            // stop the normal create flow
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        // ✅ Normal create flow reaches here
        $this->attachCategoriesToBrand($this->record->id, $this->record->merchant_id);

        // ✅ logo upload
        $this->handleLogoUpload();
    }

    private function attachCategoriesToBrand(string $brandId, string $merchantId): void
    {
        foreach ($this->categoryIds as $categoryId) {
            Log::info("Attaching category {$categoryId} to brand {$brandId}");

            BrandCategory::firstOrCreate([
                'merchant_id' => $merchantId,
                'brand_id'    => $brandId,
                'category_id' => $categoryId,
            ]);
        }
    }

    private function handleLogoUpload(): void
    {
        $state = $this->form->getRawState();
        $path  = collect($state['brand_logo'] ?? null)->first();

        if (! $path) {
            return;
        }

        // optional: replace old logo if exists
        $this->record->logo()?->delete();

        $this->record->logo()->create([
            'merchant_id' => $this->record->merchant_id,
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::BRAND_LOGO,
            'photo_url'   => $path,
        ]);
    }

}
