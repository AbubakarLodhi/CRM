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
        $data = $this->form->getState();

        $path = is_array($data['business_logo'] ?? null)
            ? $data['business_logo'][0]
            : $data['business_logo'] ?? null;

        if ($path) {
            $this->record->logo()?->delete();

            $this->record->logo()->create([
                'merchant_id' => $this->record->merchant_id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::BUSINESS_LOGO,
                'photo_url'   => $path, // ✅ STRING ONLY
            ]);
        }
    }

}
