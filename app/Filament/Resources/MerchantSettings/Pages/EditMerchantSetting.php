<?php

namespace App\Filament\Resources\MerchantSettings\Pages;

use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use App\Filament\Resources\MerchantSettings\MerchantSettingResource;
use App\Models\MerchantSetting;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMerchantSetting extends EditRecord
{
    protected static string $resource = MerchantSettingResource::class;

    protected static ?string $title = 'Merchant Settings';

    protected function resolveRecord($key): Model
    {
        if (auth('merchant')->check()) {
            return MerchantSetting::where(
                'merchant_id',
                auth('merchant')->id()
            )->firstOrFail();
        }

        return parent::resolveRecord($key); // admin
    }

    protected function getHeaderActions(): array
    {
        return auth('merchant')->check()
            ? []   // no delete for merchant
            : [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // ✅ MERCHANT PANEL
        if (auth('merchant')->check()) {
            $merchant = auth('merchant')->user();

            $data['merchant_logo'] = $merchant->logo
                ? [$merchant->logo->photo_url]
                : null;

            $data['profile_photo'] = $merchant->profilePhoto
                ? [$merchant->profilePhoto->photo_url]
                : null;

            return $data;
        }

        // ✅ ADMIN PANEL
        if ($this->record?->merchant) {
            $data['merchant_logo'] = $this->record->merchant->logo
                ? [$this->record->merchant->logo->photo_url]
                : null;

            $data['profile_photo'] = $this->record->merchant->profilePhoto
                ? [$this->record->merchant->profilePhoto->photo_url]
                : null;
        }

        return $data;
    }




    protected function afterSave(): void
    {
        $state = $this->form->getRawState();
        $merchant = auth('merchant')->user();

        /* ===== PROFILE PHOTO ===== */
        if (array_key_exists('profile_photo', $state)) {
            if ($profile = collect($state['profile_photo'])->first()) {
                $merchant->profilePhoto()?->delete();

                $merchant->profilePhoto()->create([
                    'merchant_id' => $merchant->id,
                    'type'        => AttachmentType::IMAGE,
                    'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
                    'photo_url'   => $profile,
                ]);
            } else {
                // ✅ REMOVED
                $merchant->profilePhoto()?->delete();
            }
        }

        /* ===== MERCHANT LOGO ===== */
        if (array_key_exists('merchant_logo', $state)) {
            if ($logo = collect($state['merchant_logo'])->first()) {
                $merchant->logo()?->delete();

                $merchant->logo()->create([
                    'merchant_id' => $merchant->id,
                    'type'        => AttachmentType::IMAGE,
                    'meta_type'   => AttachmentMetaType::MERCHANT_LOGO,
                    'photo_url'   => $logo,
                ]);
            } else {
                // ✅ THIS WAS MISSING
                $merchant->logo()?->delete();
            }
        }

        $this->redirect(request()->header('Referer'), navigate: false);
    }


}
