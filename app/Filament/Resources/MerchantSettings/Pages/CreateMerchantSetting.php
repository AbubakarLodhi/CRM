<?php

namespace App\Filament\Resources\MerchantSettings\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\MerchantSettings\MerchantSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMerchantSetting extends CreateRecord
{
    protected static string $resource = MerchantSettingResource::class;
    protected function afterCreate(): void
    {
        $state = $this->form->getRawState();
        $merchant = auth('merchant')->user();

        /* ===== PROFILE PHOTO ===== */
        if ($profile = collect($state['profile_photo'] ?? null)->first()) {
            $merchant->profilePhoto()?->delete();

            $merchant->profilePhoto()->create([
                'merchant_id' => $merchant->id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
                'photo_url'   => $profile,
            ]);
        }

        /* ===== MERCHANT LOGO ===== */
        if ($logo = collect($state['merchant_logo'] ?? null)->first()) {
            $merchant->logo()?->delete();

            $merchant->logo()->create([
                'merchant_id' => $merchant->id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::MERCHANT_LOGO,
                'photo_url'   => $logo,
            ]);
        }
    }
}
