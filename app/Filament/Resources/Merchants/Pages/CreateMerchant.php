<?php

namespace App\Filament\Resources\Merchants\Pages;

use App\Filament\Resources\Merchants\MerchantResource;
use Filament\Resources\Pages\CreateRecord;
use App\Enums\AttachmentType;
use App\Enums\AttachmentMetaType;

class CreateMerchant extends CreateRecord
{
    protected static string $resource = MerchantResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getRawState();

        /* ======================
         | PROFILE PHOTO
         |======================*/
        $profilePath = collect($state['profile_photo'] ?? null)->first();

        if ($profilePath) {
            $this->record->profilePhoto()->create([
                'merchant_id' => $this->record->id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
                'photo_url'   => $profilePath,
            ]);
        }

        /* ======================
         | MERCHANT LOGO
         |======================*/
        $logoPath = collect($state['merchant_logo'] ?? null)->first();

        if ($logoPath) {
            $this->record->logo()->create([
                'merchant_id' => $this->record->id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::MERCHANT_LOGO,
                'photo_url'   => $logoPath,
            ]);
        }
    }



}
