<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\Businesses\BusinessResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBusiness extends CreateRecord
{
    protected static string $resource = BusinessResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();

        if (empty($data['business_logo']) || ! is_array($data['business_logo'])) {
            return;
        }

        // ✅ Always extract the FIRST VALUE, not index 0
        $path = collect($data['business_logo'])->first();

        if (! $path) {
            return;
        }

        $this->record->logo()?->delete();

        $this->record->logo()->create([
            'merchant_id' => $this->record->merchant_id,
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::BUSINESS_LOGO,
            'photo_url'   => $path, // ✅ ALWAYS STRING
        ]);
    }

}
