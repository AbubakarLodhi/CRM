<?php

namespace App\Filament\Resources\Admins\Pages;

use App\Filament\Resources\Admins\AdminResource;
use Filament\Resources\Pages\CreateRecord;
use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();

        if (empty($data['profile_photo'])) {
            return;
        }

        $this->record->profilePhoto()->create([
            'merchant_id' => null, // admins are global
            'type'        => AttachmentType::IMAGE,
            'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
            'photo_url'   => $data['profile_photo'],
        ]);
    }

}
