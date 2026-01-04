<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;


class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();

        if (! empty($data['businesses'])) {
            foreach ($data['businesses'] as $businessId) {
                $this->record->businesses()->attach($businessId, [
                    'id' => \Illuminate\Support\Str::uuid(),
                ]);
            }
        }

        if (! empty($data['branches'])) {
            foreach ($data['branches'] as $branchId) {
                $this->record->branches()->attach($branchId, [
                    'id' => \Illuminate\Support\Str::uuid(),
                ]);
            }
        }

        if (! empty($data['profile_photo'])) {
            $this->record->profilePhoto()->create([
                'merchant_id' => $this->record->merchant_id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
                'photo_url'   => $data['profile_photo'],
            ]);
        }
    }

}
