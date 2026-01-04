<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use App\Enums\AttachmentMetaType;
use App\Enums\AttachmentType;
use Illuminate\Support\Str;


class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('users.delete', Filament::getCurrentPanel()->getAuthGuard())),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        /*
        |--------------------------------------------------------------------------
        | Sync Businesses (pivot has UUID id)
        |--------------------------------------------------------------------------
        */
        if (isset($data['businesses'])) {
            $syncData = [];

            foreach ($data['businesses'] as $businessId) {
                $syncData[$businessId] = ['id' => (string) Str::uuid()];
            }

            $this->record->businesses()->sync($syncData);
        }

        /*
        |--------------------------------------------------------------------------
        | Sync Branches (pivot has UUID id)
        |--------------------------------------------------------------------------
        */
        if (isset($data['branches'])) {
            $syncData = [];

            foreach ($data['branches'] as $branchId) {
                $syncData[$branchId] = ['id' => (string) Str::uuid()];
            }

            $this->record->branches()->sync($syncData);
        }

        /*
        |--------------------------------------------------------------------------
        | Profile Photo
        |--------------------------------------------------------------------------
        */
        if (! empty($data['profile_photo'])) {
            $this->record->profilePhoto()?->delete();

            $this->record->profilePhoto()->create([
                'merchant_id' => $this->record->merchant_id,
                'type'        => AttachmentType::IMAGE,
                'meta_type'   => AttachmentMetaType::PROFILE_PHOTO,
                'photo_url'   => $data['profile_photo'],
            ]);
        }
    }
}
