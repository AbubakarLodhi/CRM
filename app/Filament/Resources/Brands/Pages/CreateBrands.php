<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Brands\BrandsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrands extends CreateRecord
{
    protected static string $resource = BrandsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getRawState();

        $path = collect($state['brand_logo'] ?? null)->first();

        if (! $path) {
            return;
        }

        $this->record->logo()->create([
            'merchant_id' => $this->record->merchant_id,
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::BRAND_LOGO,
            'photo_url'   => $path,
        ]);
    }


}
